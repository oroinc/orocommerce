<?php

namespace Oro\Bundle\ShippingBundle\ExpressionLanguage;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

/**
 * Creates an instance of the ShippingLineItem model with decorated product.
 */
class DecoratedProductLineItemFactory
{
    private VirtualFieldsProductDecoratorFactory $virtualFieldsProductDecoratorFactory;

    public function __construct(
        VirtualFieldsProductDecoratorFactory $virtualFieldsProductDecoratorFactory
    ) {
        $this->virtualFieldsProductDecoratorFactory = $virtualFieldsProductDecoratorFactory;
    }

    /**
     * @param ShippingLineItem $shippingLineItem
     * @param int[]|Product[] $products
     *
     * @return ShippingLineItem
     */
    public function createShippingLineItemWithDecoratedProduct(
        ShippingLineItem $shippingLineItem,
        array $products
    ): ShippingLineItem {
        $product = $shippingLineItem->getProduct();

        $decoratedProduct = $product
            ? $this->virtualFieldsProductDecoratorFactory->createDecoratedProduct($products, $product)
            : null;

        /**
         * We should not update initial Shipping Line Item,
         * because we should work with {@see VirtualFieldsProductDecorator} only in expression language rules.
         */
        $shippingLineItemWithDecoratedProduct = clone $shippingLineItem;
        $shippingLineItemWithDecoratedProduct->setProduct($decoratedProduct);

        $shippingKitItemLineItemsWithDecoratedProduct = [];
        foreach ($shippingLineItemWithDecoratedProduct->getKitItemLineItems() as $shippingKitItemLineItem) {
            // We should not update initial Shipping Kit Item Line Item
            $shippingKitItemLineItemWithDecoratedProduct = clone $shippingKitItemLineItem;
            $decoratedShippingKitItemLineItemProduct = $shippingKitItemLineItemWithDecoratedProduct->getProduct()
                ? $this->virtualFieldsProductDecoratorFactory->createDecoratedProduct(
                    $products,
                    $shippingKitItemLineItemWithDecoratedProduct->getProduct()
                )
                : null;

            $shippingKitItemLineItemWithDecoratedProduct->setProduct($decoratedShippingKitItemLineItemProduct);

            $shippingKitItemLineItemsWithDecoratedProduct[] = $shippingKitItemLineItemWithDecoratedProduct;
        }
        $shippingLineItemWithDecoratedProduct->setKitItemLineItems(
            new ArrayCollection($shippingKitItemLineItemsWithDecoratedProduct)
        );

        return $shippingLineItemWithDecoratedProduct;
    }
}
