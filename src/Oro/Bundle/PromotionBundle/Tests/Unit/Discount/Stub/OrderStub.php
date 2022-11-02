<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub;

use Oro\Bundle\OrderBundle\Entity\Order;

class OrderStub extends Order
{
    private bool $disablePromotions = false;

    public function getDisablePromotions(): bool
    {
        return $this->disablePromotions;
    }

    public function setDisablePromotions(bool $disablePromotions): static
    {
        $this->disablePromotions = $disablePromotions;

        return $this;
    }
}
