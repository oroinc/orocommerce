<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\OrderBundle\Entity\Order as BaseOrder;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon as BaseAppliedCoupon;

class AppliedCoupon extends BaseAppliedCoupon
{
    private ?BaseOrder $order = null;

    public function setOrder(?BaseOrder $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): ?BaseOrder
    {
        return $this->order;
    }
}
