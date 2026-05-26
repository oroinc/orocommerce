<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;

/**
 * Synchronizes order draft to order before totals are calculated.
 */
final class OrderDraftAwareTotalCalculateListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private readonly OrderDraftManager $orderDraftManager,
        private readonly FrontendHelper $frontendHelper,
    ) {
    }

    public function onBeforeTotalCalculate(TotalCalculateBeforeEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $entity = $event->getEntity();
        if (!$entity instanceof Order) {
            return;
        }

        if ($this->frontendHelper->isFrontendRequest()) {
            return;
        }

        $this->orderDraftManager->loadFromEntityDraft($entity);
    }
}
