<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
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

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EnumValueProvider $enumValueProvider
     * @param LoggerInterface $logger
     * @param ConfigManager $configManager
     */
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

    /**
     * @param CacheProvider $cache
     * @param int $lifeTime
     */
    public function setCache(CacheProvider $cache, int $lifeTime = 0): void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getPossibleValues($fieldName)
    {
        $data = $this->cache->fetch($fieldName);
        if (!\is_array($data)) {
            $config = $this->configManager->getConfigFieldModel(Product::class, $fieldName);
            $extendConfig = $config->toArray('extend');

            $data = $this->enumValueProvider->getEnumChoices($extendConfig['target_entity']);

            $this->cache->save($fieldName, $data, $this->cacheLifeTime);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getScalarValue($value)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getHumanReadableValue($fieldName, $value)
    {
        $possibleValue = $this->getPossibleValues($fieldName);
        $fieldIdentifier = $this->getScalarValue($value);

        $value = array_search($fieldIdentifier, $possibleValue, false);
        if (!$value) {
            $value = 'N/A';
            $this->logger->error(
                'Can not find configurable attribute "{attributeValue}" in list of available attributes.' .
                'Available: "{availableAttributes}"',
                [
                    'attribute' => (string)$fieldIdentifier,
                    'availableAttributes' => implode(', ', array_values($possibleValue)),
                ]
            );
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
