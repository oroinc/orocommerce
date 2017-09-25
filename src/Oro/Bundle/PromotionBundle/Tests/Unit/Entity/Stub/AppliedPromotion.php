<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion as BaseAppliedPromotion;

class AppliedPromotion extends BaseAppliedPromotion
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }
}
