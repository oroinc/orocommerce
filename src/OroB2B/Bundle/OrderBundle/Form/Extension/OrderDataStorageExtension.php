<?php

namespace Oro\Bundle\OrderBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

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

        $quantity = 1;
        if (array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $itemData)) {
            $quantity = $itemData[ProductDataStorage::PRODUCT_QUANTITY_KEY];
        }
        $lineItem->setQuantity($quantity);

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
