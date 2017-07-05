<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler;

use Psr\Log\LoggerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;

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
     * @param DoctrineHelper $doctrineHelper
     * @param EnumValueProvider $enumValueProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EnumValueProvider $enumValueProvider,
        LoggerInterface $logger
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->enumValueProvider = $enumValueProvider;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getPossibleValues($fieldName)
    {
        $enumCode = ExtendHelper::generateEnumCode(Product::class, $fieldName);

        return $this->enumValueProvider->getEnumChoicesByCode($enumCode);
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

        $value = 'N/A';
        if (!array_key_exists($fieldIdentifier, $possibleValue)) {
            $this->logger->error(
                'Can not find configurable attribute "{attributeValue}" in list of available attributes.' .
                'Available: "{availableAttributes}"',
                [
                    'attribute' => (string)$fieldIdentifier,
                    'availableAttributes' => implode(', ', array_keys($possibleValue)),
                ]
            );
        } else {
            $value = $possibleValue[$fieldIdentifier];
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
