<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;

/**
 * Listener creates applied promotion(s) by given entry.
 */
class OrderEntityListener
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private AppliedPromotionManager $appliedPromotionManager
    ) {
    }

    public function prePersist(Order $order): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        if ($order->getDraftSessionUuid()) {
            // Prevents the promotions from being applied to order draft because it is not a completed order.
            return;
        }

        $this->appliedPromotionManager->createAppliedPromotions($order);
    }
}
