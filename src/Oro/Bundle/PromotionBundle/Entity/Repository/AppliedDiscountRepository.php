<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class AppliedDiscountRepository extends EntityRepository
{
    /**
     * @param Order $order
     * @return array
     */
    public function findByOrder(Order $order)
    {
        return $this->findBy(['order' => $order]);
    }

    /**
     * @param OrderLineItem $orderLineItem
     * @return array
     */
    public function findByLineItem(OrderLineItem $orderLineItem)
    {
        return $this->findBy(['lineItem' => $orderLineItem]);
    }
}
