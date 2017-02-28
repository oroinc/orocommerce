<?php

namespace Oro\Bundle\ShippingBundle\ExpressionLanguage;

use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

class DecoratedProductLineItemFactory
{
    /**
     * @var VirtualFieldsProductDecoratorFactory
     */
    private $virtualFieldsProductDecoratorFactory;

    /**
     * @param VirtualFieldsProductDecoratorFactory $virtualFieldsProductDecoratorFactory
     */
    public function __construct(VirtualFieldsProductDecoratorFactory $virtualFieldsProductDecoratorFactory)
    {
        $this->virtualFieldsProductDecoratorFactory = $virtualFieldsProductDecoratorFactory;
    }

    /**
     * @param ShippingLineItemInterface[] $lineItems
     * @param ShippingLineItemInterface $lineItem
     *
     * @return ShippingLineItem
     */
    public function createLineItemWithDecoratedProductByLineItem(array $lineItems, ShippingLineItemInterface $lineItem)
    {
        $product = $lineItem->getProduct();

        $decoratedProduct = $product
            ? $this->virtualFieldsProductDecoratorFactory->createDecoratedProductByProductHolders($lineItems, $product)
            : null;

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
                ShippingLineItem::FIELD_PRODUCT => $decoratedProduct,
            ]
        );
    }
}
