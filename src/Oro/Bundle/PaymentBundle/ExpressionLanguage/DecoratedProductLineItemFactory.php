<?php

namespace Oro\Bundle\PaymentBundle\ExpressionLanguage;

use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;

/**
 * Creates an instance of the PaymentLineItem model with decorated product.
 */
class DecoratedProductLineItemFactory
{
    /**
     * @var VirtualFieldsProductDecoratorFactory
     */
    private $virtualFieldsProductDecoratorFactory;

    public function __construct(VirtualFieldsProductDecoratorFactory $virtualFieldsProductDecoratorFactory)
    {
        $this->virtualFieldsProductDecoratorFactory = $virtualFieldsProductDecoratorFactory;
    }

    /**
     * @param PaymentLineItemInterface $lineItem
     * @param int[]|Product[] $products
     *
     * @return PaymentLineItem
     */
    public function createPaymentLineItemWithDecoratedProduct(
        PaymentLineItemInterface $lineItem,
        array $products
    ): PaymentLineItem {
        $product = $lineItem->getProduct();

        $decoratedProduct = $product
            ? $this->virtualFieldsProductDecoratorFactory->createDecoratedProduct($products, $product)
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
