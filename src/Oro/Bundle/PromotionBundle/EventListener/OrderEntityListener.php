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

    public function __construct(AppliedPromotionManager $appliedPromotionManager)
    {
        $this->appliedPromotionManager = $appliedPromotionManager;
    }

    public function prePersist(Order $order)
    {
        $this->appliedPromotionManager->createAppliedPromotions($order);
    }
}
