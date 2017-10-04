<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class AppliedDiscountRepository extends EntityRepository
{
    /**
     * @param OrderLineItem $orderLineItem
     * @return array
     */
    public function findByLineItem(OrderLineItem $orderLineItem)
    {
        return $this->findBy(['lineItem' => $orderLineItem]);
    }
}
