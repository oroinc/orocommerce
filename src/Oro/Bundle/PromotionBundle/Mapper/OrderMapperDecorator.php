<?php

namespace Oro\Bundle\PromotionBundle\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;

/**
 * Order mapper promotions decorator.
 */
class OrderMapperDecorator implements MapperInterface
{
    public function __construct(
        private MapperInterface            $orderMapper,
        private PromotionAwareEntityHelper $promotionAwareHelper
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function map(Checkout $checkout, array $data = [], array $skipped = [])
    {
        $skipped['appliedCoupons'] = true;
        $order = $this->orderMapper->map($checkout, $data, $skipped);
        if ($this->promotionAwareHelper->isCouponAware($checkout)) {
            foreach ($checkout->getAppliedCoupons() as $appliedCoupon) {
                $order->addAppliedCoupon($this->getAppliedCouponCopy($appliedCoupon));
            }
        }

        return $order;
    }

    private function getAppliedCouponCopy(AppliedCoupon $appliedCoupon): AppliedCoupon
    {
        return (new AppliedCoupon())
            ->setSourceCouponId($appliedCoupon->getSourceCouponId())
            ->setSourcePromotionId($appliedCoupon->getSourcePromotionId())
            ->setCouponCode($appliedCoupon->getCouponCode());
    }
}
