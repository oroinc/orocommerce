<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;

/**
 * Order form listener that creates applied promotions for Order
 */
class OrderFormListener
{
    /**
     * @var AppliedPromotionManager
     */
    private $appliedPromotionManager;

    public function __construct(AppliedPromotionManager $appliedPromotionManager)
    {
        $this->appliedPromotionManager = $appliedPromotionManager;
    }

    public function onBeforeFlush(AfterFormProcessEvent $event)
    {
        /** @var Order $order */
        $order = $event->getData();
        $this->deactivateRemovedAppliedPromotions($order);

        if ($order->getId()) {
            $this->appliedPromotionManager->createAppliedPromotions($order, true);
        }
    }

    private function deactivateRemovedAppliedPromotions(Order $order): void
    {
        /** @var PersistentCollection $appliedPromotions */
        $appliedPromotions = $order->getAppliedPromotions();
        /** @var AppliedPromotion $appliedPromotion */
        foreach ($appliedPromotions as $appliedPromotion) {
            if ($appliedPromotion->isRemoved()) {
                $appliedPromotion->setActive(false);
            }
        }
    }
}
