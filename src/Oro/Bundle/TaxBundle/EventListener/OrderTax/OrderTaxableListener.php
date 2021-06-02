<?php

namespace Oro\Bundle\TaxBundle\EventListener\OrderTax;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OrderLineItemRequiredTaxRecalculationSpecification;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OrderRequiredTaxRecalculationSpecification;

/**
 * Handles skip order tax recalculation event.
 */
class OrderTaxableListener extends AbstractTaxableListener
{
    public function onSkipOrderTaxRecalculation(SkipOrderTaxRecalculationEvent $event): void
    {
        $taxable = $event->getTaxable();

        $uow = $this->getUnitOfWork($taxable);
        if (!$uow instanceof UnitOfWork) {
            return;
        }

        $entity = $uow->tryGetById($taxable->getIdentifier(), $taxable->getClassName());

        if ($entity instanceof Order) {
            $event->setSkipOrderTaxRecalculation($this->isSkipOrderTaxRecalculation($uow, $entity));
        }
    }

    private function isSkipOrderTaxRecalculation(UnitOfWork $uow, Order $order): bool
    {
        if ($this->isOrderTaxRecalculationRequired($order, $uow)) {
            // Recalculation is required.
            return false;
        }

        $lineItemRequiredTaxRecalculationSpecification = new OrderLineItemRequiredTaxRecalculationSpecification($uow);
        foreach ($order->getLineItems() as $orderLineItem) {
            if ($lineItemRequiredTaxRecalculationSpecification->isSatisfiedBy($orderLineItem)) {
                // Recalculation is required.
                return false;
            }
        }

        // Recalculation is not required.
        return true;
    }

    private function isOrderTaxRecalculationRequired(Order $order, UnitOfWork $uow): bool
    {
        $specification = new OrderRequiredTaxRecalculationSpecification($uow);

        return $specification->isSatisfiedBy($order);
    }
}
