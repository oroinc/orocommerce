<?php

namespace Oro\Bundle\PaymentBundle\ExpressionLanguage;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;

/**
 * Creates an instance of the {@see PaymentLineItem} model with decorated product.
 */
class DecoratedProductLineItemFactory
{
    private VirtualFieldsProductDecoratorFactory $virtualFieldsProductDecoratorFactory;

    public function __construct(VirtualFieldsProductDecoratorFactory $virtualFieldsProductDecoratorFactory)
    {
        $this->virtualFieldsProductDecoratorFactory = $virtualFieldsProductDecoratorFactory;
    }

    /**
     * @param PaymentLineItem $paymentLineItem
     * @param int[]|Product[] $products
     *
     * @return PaymentLineItem
     */
    public function createPaymentLineItemWithDecoratedProduct(
        PaymentLineItem $paymentLineItem,
        array $products
    ): PaymentLineItem {
        $product = $paymentLineItem->getProduct();

        $decoratedProduct = $product
            ? $this->virtualFieldsProductDecoratorFactory->createDecoratedProduct($products, $product)
            : null;

        /**
         * We should not update initial Payment Line Item,
         * because we should work with {@see VirtualFieldsProductDecorator} only in expression language rules.
         */
        $paymentLineItemWithDecoratedProduct = clone $paymentLineItem;
        $paymentLineItemWithDecoratedProduct->setProduct($decoratedProduct);

        $paymentKitItemLineItemsWithDecoratedProduct = [];
        foreach ($paymentLineItemWithDecoratedProduct->getKitItemLineItems() as $paymentKitItemLineItem) {
            // We should not update initial Payment Kit Item Line Item
            $paymentKitItemLineItemWithDecoratedProduct = clone $paymentKitItemLineItem;
            $decoratedPaymentKitItemLineItemProduct = $paymentKitItemLineItemWithDecoratedProduct->getProduct()
                ? $this->virtualFieldsProductDecoratorFactory->createDecoratedProduct(
                    $products,
                    $paymentKitItemLineItemWithDecoratedProduct->getProduct()
                )
                : null;

            $paymentKitItemLineItemWithDecoratedProduct->setProduct($decoratedPaymentKitItemLineItemProduct);

            $paymentKitItemLineItemsWithDecoratedProduct[] = $paymentKitItemLineItemWithDecoratedProduct;
        }
        $paymentLineItemWithDecoratedProduct->setKitItemLineItems(
            new ArrayCollection($paymentKitItemLineItemsWithDecoratedProduct)
        );

        return $paymentLineItemWithDecoratedProduct;
    }
}
