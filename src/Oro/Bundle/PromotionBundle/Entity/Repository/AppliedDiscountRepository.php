<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrderBundle\Entity\Order;

class AppliedDiscountRepository extends EntityRepository
{
    /**
     * @param Order $order
     * @return mixed
     */
    public function deleteByOrder(Order $order)
    {
        $qb = $this->createQueryBuilder('ad');

        return $qb
            ->delete()
            ->where($qb->expr()->eq('ad.order', ':order'))
            ->setParameter('order', $order)
            ->getQuery()->execute();
    }
}
