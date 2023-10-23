<?php

namespace Oro\Bundle\PaymentBundle\ExpressionLanguage;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
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

        $params = [
            PaymentLineItem::FIELD_PRICE => $lineItem->getPrice(),
            PaymentLineItem::FIELD_PRODUCT_UNIT => $lineItem->getProductUnit(),
            PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $lineItem->getProductUnitCode(),
            PaymentLineItem::FIELD_QUANTITY => $lineItem->getQuantity(),
            PaymentLineItem::FIELD_PRODUCT_HOLDER => $lineItem->getProductHolder(),
            PaymentLineItem::FIELD_PRODUCT_SKU => $lineItem->getProductSku(),
            PaymentLineItem::FIELD_PRODUCT => $decoratedProduct,
        ];

        if ($lineItem instanceof PaymentLineItem) {
            $paymentKitItemLineItemsWithDecoratedProduct = [];
            foreach ($lineItem->getKitItemLineItems() as $paymentKitItemLineItem) {
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

            $params[PaymentLineItem::FIELD_CHECKSUM] = $lineItem->getChecksum();
            $params[PaymentLineItem::FIELD_KIT_ITEM_LINE_ITEMS] = new ArrayCollection(
                $paymentKitItemLineItemsWithDecoratedProduct
            );
        }

        return new PaymentLineItem($params);
    }
}
