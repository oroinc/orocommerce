<?php

namespace OroB2B\Bundle\OrderBundle\Form\Extension;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class OrderDataStorageExtension extends AbstractProductDataStorageExtension
{
    /**
     * {@inheritdoc}
     */
    protected function addItem(Product $product, $entity, array $itemData = [])
    {
        if (!$entity instanceof Order) {
            return;
        }

        $lineItem = new OrderLineItem();
        $lineItem
            ->setProduct($product)
            ->setProductSku($product->getSku());

        if (array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $itemData)) {
            $lineItem->setQuantity($itemData[ProductDataStorage::PRODUCT_QUANTITY_KEY]);
        }

        $this->fillEntityData($lineItem, $itemData);

        if (!$lineItem->getProductUnit()) {
            /** @var ProductUnitPrecision $unitPrecision */
            $unitPrecision = $product->getUnitPrecisions()->first();
            if (!$unitPrecision) {
                return;
            }

            /** @var ProductUnit $unit */
            $unit = $unitPrecision->getUnit();
            if (!$unit) {
                return;
            }

            $lineItem->setProductUnit($unit);
            $lineItem->setProductUnitCode($unit->getCode());
        }

        if ($lineItem->getProduct()) {
            $entity->addLineItem($lineItem);
        }
    }
}
