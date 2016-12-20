<?php

namespace Oro\Bundle\ShippingBundle\ExpressionLanguage;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class DecoratedProductLineItemFactory
{
    /**
     * @var EntityFieldProvider
     */
    protected $entityFieldProvider;

    /**
     * @var SelectQueryConverter
     */
    protected $converter;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @param EntityFieldProvider $entityFieldProvider
     * @param SelectQueryConverter $converter
     * @param ManagerRegistry $doctrine
     * @param FieldHelper $fieldHelper
     */
    public function __construct(
        EntityFieldProvider $entityFieldProvider,
        SelectQueryConverter $converter,
        ManagerRegistry $doctrine,
        FieldHelper $fieldHelper
    ) {
        $this->entityFieldProvider = $entityFieldProvider;
        $this->converter = $converter;
        $this->doctrine = $doctrine;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @param array $lineItems
     * @param ShippingLineItemInterface $lineItem
     *
     * @return ShippingLineItem
     */
    public function createLineItemWithDecoratedProductByLineItem(array $lineItems, ShippingLineItemInterface $lineItem)
    {
        return new ShippingLineItem(
            [
                ShippingLineItem::FIELD_PRICE => $lineItem->getPrice(),
                ShippingLineItem::FIELD_PRODUCT_UNIT => $lineItem->getProductUnit(),
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $lineItem->getProductUnitCode(),
                ShippingLineItem::FIELD_QUANTITY => $lineItem->getQuantity(),
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $lineItem->getProductHolder(),
                ShippingLineItem::FIELD_PRODUCT_SKU => $lineItem->getProductSku(),
                ShippingLineItem::FIELD_WEIGHT => $lineItem->getWeight(),
                ShippingLineItem::FIELD_DIMENSIONS => $lineItem->getDimensions(),
                ShippingLineItem::FIELD_PRODUCT => $this->createDecoratedProduct($lineItems, $lineItem->getProduct()),
            ]
        );
    }

    /**
     * @param array $lineItems
     * @param Product $product
     *
     * @return ProductDecorator
     */
    private function createDecoratedProduct(array $lineItems, Product $product)
    {
        return new ProductDecorator(
            $this->entityFieldProvider,
            $this->converter,
            $this->doctrine,
            $this->fieldHelper,
            array_map(
                function (ShippingLineItemInterface $lineItem) {
                    return $lineItem->getProduct();
                },
                $lineItems
            ),
            $product
        );
    }
}
