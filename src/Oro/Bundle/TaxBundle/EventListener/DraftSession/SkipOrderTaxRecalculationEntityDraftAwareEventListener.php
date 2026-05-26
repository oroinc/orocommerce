<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\EventListener\DraftSession;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\EventListener\OrderTax\AbstractTaxableListener;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Util\EntityDraftUtils;

/**
 * Skips tax recalculation for orders drafts.
 */
class SkipOrderTaxRecalculationEntityDraftAwareEventListener extends AbstractTaxableListener implements
    FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public function onSkipOrderTaxRecalculation(SkipOrderTaxRecalculationEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $taxable = $event->getTaxable();

        $uow = $this->getUnitOfWork($taxable);
        if (!$uow instanceof UnitOfWork) {
            return;
        }

        $entity = $uow->tryGetById($taxable->getIdentifier(), $taxable->getClassName());

        if ($entity instanceof EntityDraftAwareInterface && EntityDraftUtils::isEntityDraft($entity)) {
            $event->setSkipOrderTaxRecalculation(true);
        }
    }
}
