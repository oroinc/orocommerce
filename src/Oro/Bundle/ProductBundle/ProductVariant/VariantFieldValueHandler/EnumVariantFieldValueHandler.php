<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Provides easy way to work with the extended Enum fields of the Product entity.
 */
class EnumVariantFieldValueHandler implements ProductVariantFieldValueHandlerInterface
{
    public const TYPE = 'enum';

    private DoctrineHelper $doctrineHelper;
    private EnumOptionsProvider $enumOptionsProvider;
    private LoggerInterface $logger;
    private ConfigManager $configManager;
    private CacheInterface $cache;
    private int $cacheLifeTime = 0;
    private LocalizationHelper $localizationHelper;
    private LocaleSettings $localeSettings;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EnumOptionsProvider $enumOptionsProvider,
        LoggerInterface $logger,
        ConfigManager $configManager,
        LocalizationHelper $localizationHelper,
        LocaleSettings $localeSettings
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->enumOptionsProvider = $enumOptionsProvider;
        $this->logger = $logger;
        $this->configManager = $configManager;
        $this->localizationHelper = $localizationHelper;
        $this->localeSettings = $localeSettings;
        $this->cache = new ArrayAdapter($this->cacheLifeTime, false);
    }

    public function setCache(CacheInterface $cache, int $lifeTime = 0): void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    #[\Override]
    public function getPossibleValues(string $fieldName): array
    {
        $key = UniversalCacheKeyGenerator::normalizeCacheKey(sprintf('%s|%s', $fieldName, $this->getLocaleKey()));
        return $this->cache->get($key, function (ItemInterface $item) use ($fieldName) {
            if ($this->cacheLifeTime > 0) {
                $item->expiresAfter($this->cacheLifeTime);
            }
            $config = $this->configManager->getConfigFieldModel(Product::class, $fieldName);
            if ($config instanceof FieldConfigModel) {
                $extendConfig = $config->toArray('enum');

                return $this->enumOptionsProvider->getEnumChoicesWithNonUniqueTranslation($extendConfig['enum_code']);
            }

            return [];
        });
    }

    #[\Override]
    public function getScalarValue(mixed $value): mixed
    {
        if (!$value instanceof EnumOptionInterface) {
            return null;
        }

        return $this->doctrineHelper->getSingleEntityIdentifier($value);
    }

    #[\Override]
    public function getHumanReadableValue(string $fieldName, mixed $value): mixed
    {
        $fieldIdentifier = $this->getScalarValue($value);

        if ($fieldIdentifier !== null) {
            $possibleValues = $this->getPossibleValues($fieldName);
            if (isset($possibleValues[$fieldIdentifier])) {
                return $possibleValues[$fieldIdentifier];
            }

            $this->logger->error(
                'Can not find configurable attribute "{attribute}" in list of available attributes.' .
                'Available: "{availableAttributes}"',
                [
                    'attribute' => (string)$fieldIdentifier,
                    'availableAttributes' => implode(', ', array_keys($possibleValues)),
                ]
            );
        }

        return 'N/A';
    }

    private function getLocaleKey(): string
    {
        return $this->localizationHelper->getCurrentLocalization()
            ? $this->localizationHelper->getCurrentLocalization()->getFormattingCode()
            : $this->localeSettings->getLocale();
    }

    #[\Override]
    public function getType(): string
    {
        return self::TYPE;
    }
}
