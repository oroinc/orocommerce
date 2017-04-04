<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;

class EnumVariantFieldValueHandler implements ProductVariantFieldValueHandlerInterface
{
    const TYPE = 'enum';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EnumValueProvider */
    private $enumValueProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EnumValueProvider $enumValueProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, EnumValueProvider $enumValueProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->enumValueProvider = $enumValueProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPossibleValues($variantFieldName)
    {
        $enumCode = ExtendHelper::generateEnumCode(Product::class, $variantFieldName);

        return $this->enumValueProvider->getEnumChoicesByCode($enumCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getScalarValue($variantValue)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($variantValue);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
