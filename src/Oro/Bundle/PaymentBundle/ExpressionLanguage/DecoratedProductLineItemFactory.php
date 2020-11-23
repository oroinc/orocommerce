<?php

namespace Oro\Bundle\PaymentBundle\ExpressionLanguage;

use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
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

        return $this->createPaymentLineItem($lineItem, $decoratedProduct);
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

        return $this->createPaymentLineItem($lineItem, $decoratedProduct);
    }

    /**
     * @param PaymentLineItemInterface $lineItem
     * @param null|VirtualFieldsProductDecorator $decoratedProduct
     *
     * @return PaymentLineItem
     */
    private function createPaymentLineItem(
        PaymentLineItemInterface $lineItem,
        ?VirtualFieldsProductDecorator $decoratedProduct
    ): PaymentLineItem {
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
