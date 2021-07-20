<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;

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

        if ($order->getId()) {
            $this->appliedPromotionManager->createAppliedPromotions($order, true);
        }
    }
}
