<?php

namespace Oro\Bundle\ShippingBundle\ExpressionLanguage;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

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
     * @param ShippingLineItemInterface $lineItem
     * @param int[]|Product[] $products
     *
     * @return ShippingLineItem
     */
    public function createShippingLineItemWithDecoratedProduct(
        ShippingLineItemInterface $lineItem,
        array $products
    ): ShippingLineItem {
        $product = $lineItem->getProduct();

        $decoratedProduct = $product
            ? $this->virtualFieldsProductDecoratorFactory->createDecoratedProduct($products, $product)
            : null;

        $params = [
            ShippingLineItem::FIELD_PRICE => $lineItem->getPrice(),
            ShippingLineItem::FIELD_PRODUCT_UNIT => $lineItem->getProductUnit(),
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $lineItem->getProductUnitCode(),
            ShippingLineItem::FIELD_QUANTITY => $lineItem->getQuantity(),
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $lineItem->getProductHolder(),
            ShippingLineItem::FIELD_PRODUCT_SKU => $lineItem->getProductSku(),
            ShippingLineItem::FIELD_WEIGHT => $lineItem->getWeight(),
            ShippingLineItem::FIELD_DIMENSIONS => $lineItem->getDimensions(),
            ShippingLineItem::FIELD_PRODUCT => $decoratedProduct,
        ];

        if ($lineItem instanceof ShippingLineItem) {
            $shippingKitItemLineItemsWithDecoratedProduct = [];
            foreach ($lineItem->getKitItemLineItems() as $shippingKitItemLineItem) {
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

            $params[ShippingLineItem::FIELD_CHECKSUM] = $lineItem->getChecksum();
            $params[ShippingLineItem::FIELD_KIT_ITEM_LINE_ITEMS] = new ArrayCollection(
                $shippingKitItemLineItemsWithDecoratedProduct
            );
        }

        return new ShippingLineItem($params);
    }
}
