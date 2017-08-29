<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;

class AppliedPromotionRepository extends EntityRepository
{
    /**
     * @param Order $order
     * @return AppliedPromotion[]
     */
    public function findByOrder(Order $order)
    {
        return $this->findBy(['order' => $order]);
    }
}
