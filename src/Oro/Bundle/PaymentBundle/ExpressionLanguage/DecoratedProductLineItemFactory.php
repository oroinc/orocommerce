<?php

namespace Oro\Bundle\PaymentBundle\ExpressionLanguage;

use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;

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
     * @param PaymentLineItemInterface[] $lineItems
     * @param PaymentLineItemInterface $lineItem
     *
     * @return PaymentLineItem
     */
    public function createLineItemWithDecoratedProductByLineItem(array $lineItems, PaymentLineItemInterface $lineItem)
    {
        $product = $lineItem->getProduct();

        $decoratedProduct = $product
            ? $this->virtualFieldsProductDecoratorFactory->createDecoratedProductByProductHolders($lineItems, $product)
            : null;

        return new PaymentLineItem(
            [
                PaymentLineItem::FIELD_PRICE => $lineItem->getPrice(),
                PaymentLineItem::FIELD_PRODUCT_UNIT => $lineItem->getProductUnit(),
                PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $lineItem->getProductUnitCode(),
                PaymentLineItem::FIELD_QUANTITY => $lineItem->getQuantity(),
                PaymentLineItem::FIELD_PRODUCT_HOLDER => $lineItem->getProductHolder(),
                PaymentLineItem::FIELD_PRODUCT_SKU => $lineItem->getProductSku(),
                PaymentLineItem::FIELD_PRODUCT => $decoratedProduct,
            ]
        );
    }
}
