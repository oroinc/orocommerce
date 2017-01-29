<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;

class CheckoutListener
{
    /**
     * @param Checkout $checkout
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(Checkout $checkout, LifecycleEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();

        $unitOfWork->scheduleExtraUpdate(
            $checkout,
            [
                'completedData' => [null, $checkout->getCompletedData()]
            ]
        );
    }
}
