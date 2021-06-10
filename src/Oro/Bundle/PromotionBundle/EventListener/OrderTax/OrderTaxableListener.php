<?php

namespace Oro\Bundle\PromotionBundle\EventListener\OrderTax;

use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\OrderTax\Specification\OrderWithChangedPromotionsCollectionSpecification;
use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\EventListener\OrderTax\AbstractTaxableListener;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Handles skip order tax recalculation event.
 */
class OrderTaxableListener extends AbstractTaxableListener
{
    private TaxationSettingsProvider $taxationSettingsProvider;

    public function __construct(ManagerRegistry $doctrine, TaxationSettingsProvider $taxationSettingsProvider)
    {
        parent::__construct($doctrine);

        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    public function onSkipOrderTaxRecalculation(SkipOrderTaxRecalculationEvent $event): void
    {
        if (!$this->taxationSettingsProvider->isCalculateAfterPromotionsEnabled()) {
            return;
        }

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

        // Recalculation is not required.
        return true;
    }

    private function isOrderTaxRecalculationRequired(Order $order, UnitOfWork $uow): bool
    {
        $specification = new OrderWithChangedPromotionsCollectionSpecification($uow);

        return $specification->isSatisfiedBy($order);
    }
}
