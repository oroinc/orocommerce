<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\Order as BaseOrder;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;

class Order extends BaseOrder
{
    use AppliedCouponsTrait;

    /**
     * @var Collection|AppliedPromotion[]
     */
    private ?Collection $appliedPromotions = null;

    public function __construct()
    {
        parent::__construct();

        $this->appliedCoupons = new ArrayCollection();
        $this->appliedPromotions = new ArrayCollection();
    }

    public function getAppliedPromotions()
    {
        return $this->appliedPromotions;
    }

    public function removeAppliedPromotion($promotion)
    {
        $this->appliedPromotions->removeElement($promotion);
    }

    public function setAppliedPromotions($promotions)
    {
        $this->appliedPromotions = $promotions;
    }

    public function addAppliedPromotion($promotion)
    {
        $this->appliedPromotions->add($promotion);
    }

    /**
     * @param Collection $lineItems
     *
     * @return Order|void
     */
    #[\Override]
    public function setLineItems(Collection $lineItems)
    {
        $this->lineItems = $lineItems;
    }
}
