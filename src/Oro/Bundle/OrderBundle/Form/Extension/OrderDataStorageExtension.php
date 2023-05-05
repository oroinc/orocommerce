<?php

namespace Oro\Bundle\OrderBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

/**
 * The form type extension that pre-fill an order with requested products taken from the product data storage.
 */
class OrderDataStorageExtension extends AbstractProductDataStorageExtension
{
    /**
     * {@inheritDoc}
     */
    protected function addItem(Product $product, object $entity, array $itemData): void
    {
        /** @var Order $entity */

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductSku($product->getSku());
        if (\array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $itemData)) {
            $lineItem->setQuantity($itemData[ProductDataStorage::PRODUCT_QUANTITY_KEY]);
        } else {
            $lineItem->setQuantity(1);
        }

        $this->fillEntityData($lineItem, $itemData);

        if (!$lineItem->getProductUnit()) {
            $unit = $this->getDefaultProductUnit($product);
            if (null === $unit) {
                return;
            }
            $lineItem->setProductUnit($unit);
            $lineItem->setProductUnitCode($unit->getCode());
        }

        if ($lineItem->getProduct()) {
            $entity->addLineItem($lineItem);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityClass(): string
    {
        return Order::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}
