<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\OrderBundle\Entity\Order as BaseOrder;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion as BaseAppliedPromotion;

class AppliedPromotion extends BaseAppliedPromotion
{
    /**
     * @var BaseOrder
     */
    private $order;

    /**
     * @param BaseOrder $order
     * @return $this
     */
    public function setOrder(BaseOrder $order)
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): BaseOrder
    {
        return $this->order;
    }
}
