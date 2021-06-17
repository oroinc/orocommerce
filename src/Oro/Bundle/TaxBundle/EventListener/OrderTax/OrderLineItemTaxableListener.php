<?php

namespace Oro\Bundle\TaxBundle\EventListener\OrderTax;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OrderLineItemRequiredTaxRecalculationSpecification;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OrderRequiredTaxRecalculationSpecification;

/**
 * Handles skip order tax recalculation event.
 */
class OrderLineItemTaxableListener extends AbstractTaxableListener
{
    private array $orderRequiresTaxRecalculation = [];

    public function onSkipOrderTaxRecalculation(SkipOrderTaxRecalculationEvent $event): void
    {
        $taxable = $event->getTaxable();

        $uow = $this->getUnitOfWork($taxable);
        if (!$uow instanceof UnitOfWork) {
            return;
        }

        $entity = $uow->tryGetById($taxable->getIdentifier(), $taxable->getClassName());

        if ($entity instanceof OrderLineItem) {
            $event->setSkipOrderTaxRecalculation($this->isSkipOrderTaxRecalculation($uow, $entity));
        }
    }

    private function isSkipOrderTaxRecalculation(UnitOfWork $uow, OrderLineItem $orderLineItem): bool
    {
        if ($this->isOrderTaxRecalculationRequiredCached($orderLineItem->getOrder(), $uow)) {
            // Recalculation is required.
            return false;
        }

        $lineItemRequiredTaxRecalculationSpecification = new OrderLineItemRequiredTaxRecalculationSpecification($uow);
        if ($lineItemRequiredTaxRecalculationSpecification->isSatisfiedBy($orderLineItem)) {
            // Recalculation is required.
            return false;
        }

        // Recalculation is not required.
        return true;
    }

    private function isOrderTaxRecalculationRequiredCached(Order $order, UnitOfWork $uow): bool
    {
        $orderId = $order->getId();
        if (!$orderId) {
            return true;
        }

        if (!isset($this->orderRequiresTaxRecalculation[$orderId])) {
            $this->orderRequiresTaxRecalculation[$orderId] = $this->isOrderTaxRecalculationRequired($order, $uow);
        }

        return $this->orderRequiresTaxRecalculation[$orderId];
    }

    private function isOrderTaxRecalculationRequired(Order $order, UnitOfWork $uow): bool
    {
        $specification = new OrderRequiredTaxRecalculationSpecification($uow);

        return $specification->isSatisfiedBy($order);
    }

    /**
     * Clears local variable which is used for checking if order requires tax recalculation.
     */
    public function clearOrderRequiresTaxRecalculationCache(): void
    {
        $this->orderRequiresTaxRecalculation = [];
    }
}
