<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;
use Psr\Log\LoggerInterface;

class EnumVariantFieldValueHandler implements ProductVariantFieldValueHandlerInterface
{
    const TYPE = 'enum';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @var ConfigManager
     */
    protected $configManager;

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
    }

    /**
     * {@inheritdoc}
     */
    public function getPossibleValues($fieldName)
    {
        $config = $this->configManager->getConfigFieldModel(Product::class, $fieldName);
        $extendConfig = $config->toArray('extend');

        return $this->enumValueProvider->getEnumChoices($extendConfig['target_entity']);
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
