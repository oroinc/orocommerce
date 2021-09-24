<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides easy way to work with the extended Enum fields of the Product entity.
 */
class EnumVariantFieldValueHandler implements ProductVariantFieldValueHandlerInterface
{
    const TYPE = 'enum';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ConfigManager */
    protected $configManager;

    /** @var CacheProvider */
    private $cache;

    /** @var int */
    private $cacheLifeTime;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var LocaleSettings */
    private $localeSettings;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EnumValueProvider $enumValueProvider,
        LoggerInterface $logger,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->enumValueProvider = $enumValueProvider;
        $this->logger = $logger;
        $this->configManager = $configManager;
        $this->cache = new ArrayCache();
    }

    public function setCache(CacheProvider $cache, int $lifeTime = 0): void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    public function getLocalizationHelper(): LocalizationHelper
    {
        if (!$this->localizationHelper) {
            throw new \LogicException('LocalizationHelper must not be null.');
        }

        return $this->localizationHelper;
    }

    public function setLocalizationHelper(LocalizationHelper $localizationHelper): void
    {
        $this->localizationHelper = $localizationHelper;
    }

    public function getLocaleSettings(): LocaleSettings
    {
        if (!$this->localeSettings) {
            throw new \LogicException('LocaleSettings must not be null.');
        }

        return $this->localeSettings;
    }

    public function setLocaleSettings(LocaleSettings $localeSettings): void
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function getPossibleValues($fieldName)
    {
        $key = sprintf('%s|%s', $fieldName, $this->getLocaleKey());
        $data = $this->cache->fetch($key);
        if (!\is_array($data)) {
            $config = $this->configManager->getConfigFieldModel(Product::class, $fieldName);
            $extendConfig = $config->toArray('extend');

            $data = $this->enumValueProvider->getEnumChoicesWithNonUniqueTranslation($extendConfig['target_entity']);

            $this->cache->save($key, $data, $this->cacheLifeTime);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getScalarValue($value)
    {
        if (!$value instanceof AbstractEnumValue) {
            return null;
        }

        return $this->doctrineHelper->getSingleEntityIdentifier($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getHumanReadableValue($fieldName, $value)
    {
        $fieldIdentifier = $this->getScalarValue($value);

        if ($fieldIdentifier !== null) {
            $possibleValues = $this->getPossibleValues($fieldName);
            if (isset($possibleValues[$fieldIdentifier])) {
                return $possibleValues[$fieldIdentifier];
            }

            $this->logger->error(
                'Can not find configurable attribute "{attributeValue}" in list of available attributes.' .
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
        return $this->getLocalizationHelper()->getCurrentLocalization()
            ? $this->getLocalizationHelper()->getCurrentLocalization()->getFormattingCode()
            : $this->getLocaleSettings()->getLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
