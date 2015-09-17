<?php

namespace OroB2B\Bundle\OrderBundle\Form\Extension;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Extension\AbstractPostQuickAddTypeExtension;

class OrderExtension extends AbstractPostQuickAddTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItem(Product $product, $entity)
    {
        if (!$entity instanceof Order) {
            return null;
        }

        $lineItem = new OrderLineItem();
        $lineItem
            ->setProduct($product)
            ->setProductSku($product->getSku());

        $entity->addLineItem($lineItem);

        return $lineItem;
    }
}
