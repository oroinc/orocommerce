<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;

class OrderLineItemEntityListener
{
    /**
     * @var AppliedDiscountManager
     */
    private $appliedDiscountManager;

    /**
     * @param AppliedDiscountManager $appliedDiscountManager
     */
    public function __construct(AppliedDiscountManager $appliedDiscountManager)
    {
        $this->appliedDiscountManager = $appliedDiscountManager;
    }

    /**
     * @param OrderLineItem $orderLineItem
     */
    public function preRemove(OrderLineItem $orderLineItem)
    {
        $this->appliedDiscountManager->removeAppliedDiscountByOrderLineItem($orderLineItem);
    }
}
