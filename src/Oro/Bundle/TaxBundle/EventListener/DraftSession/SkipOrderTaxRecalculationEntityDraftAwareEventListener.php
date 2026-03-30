<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\EventListener\DraftSession;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\EventListener\OrderTax\AbstractTaxableListener;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;

/**
 * Skips tax recalculation for orders drafts.
 */
class SkipOrderTaxRecalculationEntityDraftAwareEventListener extends AbstractTaxableListener
{
    public function onSkipOrderTaxRecalculation(SkipOrderTaxRecalculationEvent $event): void
    {
        $taxable = $event->getTaxable();

        $uow = $this->getUnitOfWork($taxable);
        if (!$uow instanceof UnitOfWork) {
            return;
        }

        $entity = $uow->tryGetById($taxable->getIdentifier(), $taxable->getClassName());

        if ($entity instanceof EntityDraftAwareInterface && $entity->getDraftSessionUuid()) {
            $event->setSkipOrderTaxRecalculation(true);
        }
    }
}
