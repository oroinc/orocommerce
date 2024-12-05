<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\OrderBundle\Total\TotalHelper;

/**
 * Recalculate sub orders totals on edit main order.
 * Recalculate main order totals on edit sub order.
 */
class RecalculateOrdersOnSave
{
    public function __construct(
        private readonly TotalHelper $totalHelper,
        private readonly PriceMatcher $priceMatcher,
        private readonly ManagerRegistry $doctrine
    ) {
    }

    public function onBeforeFlush(AfterFormProcessEvent $event): void
    {
        $entity = $event->getData();

        if (!$entity instanceof Order) {
            return;
        }

        $em = $this->doctrine->getManagerForClass(Order::class);
        if ($entity->getParent() !== null) {
            $parent = $entity->getParent();
            $this->priceMatcher->addMatchingPrices($parent);
            $this->totalHelper->fill($parent);
            $em->persist($parent);
        } elseif (!$entity->getSubOrders()->isEmpty()) {
            foreach ($entity->getSubOrders() as $subOrder) {
                $this->priceMatcher->addMatchingPrices($subOrder);
                $this->totalHelper->fill($subOrder);
                $em->persist($subOrder);
            }
        }
    }
}
