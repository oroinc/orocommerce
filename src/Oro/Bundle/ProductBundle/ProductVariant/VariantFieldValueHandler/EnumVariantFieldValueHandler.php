<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
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
    private EnumValueProvider $enumValueProvider;
    private LoggerInterface $logger;
    private ConfigManager $configManager;
    private CacheInterface $cache;
    private int $cacheLifeTime = 0;
    private LocalizationHelper $localizationHelper;
    private LocaleSettings $localeSettings;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EnumValueProvider $enumValueProvider,
        LoggerInterface $logger,
        ConfigManager $configManager,
        LocalizationHelper $localizationHelper,
        LocaleSettings $localeSettings
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->enumValueProvider = $enumValueProvider;
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

    public function getPossibleValues(string $fieldName) : array
    {
        $key = UniversalCacheKeyGenerator::normalizeCacheKey(sprintf('%s|%s', $fieldName, $this->getLocaleKey()));
        return $this->cache->get($key, function (ItemInterface $item) use ($fieldName) {
            if ($this->cacheLifeTime > 0) {
                $item->expiresAfter($this->cacheLifeTime);
            }
            $config = $this->configManager->getConfigFieldModel(Product::class, $fieldName);
            if ($config instanceof FieldConfigModel) {
                $extendConfig = $config->toArray('extend');
                return $this->enumValueProvider->getEnumChoicesWithNonUniqueTranslation($extendConfig['target_entity']);
            }
            return null;
        });
    }

    public function getScalarValue(mixed $value) : mixed
    {
        if (!$value instanceof AbstractEnumValue) {
            return null;
        }

        return $this->doctrineHelper->getSingleEntityIdentifier($value);
    }

    public function getHumanReadableValue(string $fieldName, mixed $value) : mixed
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

    public function getType() : string
    {
        return self::TYPE;
    }
}
