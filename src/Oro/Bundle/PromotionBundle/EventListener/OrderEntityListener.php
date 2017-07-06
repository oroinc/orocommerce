<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;

class OrderEntityListener
{
    /**
     * @var AppliedDiscountManager
     */
    protected $discountManager;

    /**
     * @param AppliedDiscountManager $appliedDiscountManager
     */
    public function __construct(AppliedDiscountManager $appliedDiscountManager)
    {
        $this->discountManager = $appliedDiscountManager;
    }

    /**
     * @param Order $order
     * @param LifecycleEventArgs $args
     */
    public function prePersist(Order $order, LifecycleEventArgs $args)
    {
        foreach ($this->discountManager->createAppliedDiscounts($order) as $appliedDiscount) {
            $args->getEntityManager()->persist($appliedDiscount);
        }
    }
}
