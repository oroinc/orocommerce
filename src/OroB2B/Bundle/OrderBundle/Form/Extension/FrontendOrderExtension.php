<?php

namespace OroB2B\Bundle\OrderBundle\Form\Extension;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Extension\AbstractPostQuickAddTypeExtension;

class FrontendOrderExtension extends AbstractPostQuickAddTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FrontendOrderType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItem(Product $product, $entity)
    {
        if (!$entity instanceof Order) {
            return null;
        }

        /** @var ProductUnitPrecision $unitPrecision */
        $unitPrecision = $product->getUnitPrecisions()->first();
        if (!$unitPrecision) {
            return null;
        }

        /** @var ProductUnit $unit */
        $unit = $unitPrecision->getUnit();
        if (!$unit) {
            return null;
        }

        $lineItem = new OrderLineItem();
        $lineItem
            ->setProduct($product)
            ->setProductSku($product->getSku())
            ->setProductUnit($unit)
            ->setProductUnitCode($unit->getCode());

        $entity->addLineItem($lineItem);

        return $lineItem;
    }
}
