<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;

class OrderEntityListener
{
    /**
     * @var AppliedPromotionManager
     */
    protected $appliedPromotionManager;

    /**
     * @param AppliedPromotionManager $appliedPromotionManager
     */
    public function __construct(AppliedPromotionManager $appliedPromotionManager)
    {
        $this->appliedPromotionManager = $appliedPromotionManager;
    }

    /**
     * @param Order $order
     */
    public function prePersist(Order $order)
    {
        $this->appliedPromotionManager->createAppliedPromotions($order);
    }
}
