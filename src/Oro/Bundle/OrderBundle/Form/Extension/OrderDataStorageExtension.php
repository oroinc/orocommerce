<?php

namespace Oro\Bundle\OrderBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

/**
 * The form type extension that pre-fill an order with requested products taken from the product data storage.
 */
class OrderDataStorageExtension extends AbstractProductDataStorageExtension
{
    #[\Override]
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
        $this->setDefaultProductUnit($lineItem, $product);
        if (!$lineItem->getProductUnit()) {
            return;
        }
        $this->addKitItemLineItems($lineItem, $itemData);

        if ($lineItem->getProduct()) {
            $entity->addLineItem($lineItem);
        }
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return Order::class;
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }

    private function addKitItemLineItems(OrderLineItem $lineItem, array $itemData): void
    {
        $kitItemLineItemsData = $itemData[ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEMS_DATA_KEY] ?? [];
        foreach ($kitItemLineItemsData as $kitItemLineItemData) {
            if (
                !isset($kitItemLineItemData[ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY])
                || !isset($kitItemLineItemData[ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY])
            ) {
                continue;
            }

            $kitItemLineItem = new OrderProductKitItemLineItem();
            $this->fillEntityData($kitItemLineItem, $kitItemLineItemData);
            $this->setDefaultProductUnit($kitItemLineItem, $kitItemLineItem->getProduct());
            if (!$kitItemLineItem->getProductUnit()) {
                continue;
            }

            $lineItem->addKitItemLineItem($kitItemLineItem);
        }
    }

    private function setDefaultProductUnit(OrderLineItem|OrderProductKitItemLineItem $lineItem, Product $product): void
    {
        if (!$lineItem->getProductUnit()) {
            $unit = $this->getDefaultProductUnit($product);
            if (null === $unit) {
                return;
            }

            $lineItem->setProductUnit($unit);
            $lineItem->setProductUnitCode($unit->getCode());
        }
    }
}
