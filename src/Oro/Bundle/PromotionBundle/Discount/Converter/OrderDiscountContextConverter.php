<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;

class OrderDiscountContextConverter implements DiscountContextConverterInterface
{
    /** {@inheritdoc} */
    public function supports($sourceEntity): bool
    {
        return $sourceEntity instanceof Order;
    }

    /** {@inheritdoc} */
    public function convert($sourceEntity): DiscountContext
    {
        $discountLineItems = [];
        /**@var Order $sourceEntity */
        $orderLineItems = $sourceEntity->getLineItems();
        foreach ($orderLineItems as $orderLineItem) {
            $discountLineItems = $this->getDiscountLineItem($orderLineItem());
        }

        $discountContext = new DiscountContext();
        $discountContext->setLineItems($discountLineItems);

        return $discountContext;
    }

    /**
     * @param OrderLineItem $orderLineItem
     * @return DiscountLineItem
     */
    public function getDiscountLineItem(OrderLineItem $orderLineItem): DiscountLineItem
    {
        return (new DiscountLineItem())
            ->setProduct($orderLineItem->getProduct())
            ->setQuantity($orderLineItem->getQuantity())
            ->setProductUnit($orderLineItem->getProductUnit())
            ->setPrice($orderLineItem->getPrice())
            ->setPriceType($orderLineItem->getPriceType());
    }
}
