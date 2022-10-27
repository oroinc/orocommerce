<?php

namespace Oro\Bundle\PromotionBundle\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;

class OrderMapperDecorator implements MapperInterface
{
    /**
     * @var MapperInterface
     */
    private $orderMapper;

    public function __construct(MapperInterface $orderMapper)
    {
        $this->orderMapper = $orderMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function map(Checkout $checkout, array $data = [], array $skipped = [])
    {
        $skipped['appliedCoupons'] = true;
        /** @var AppliedCouponsAwareInterface $order */
        $order = $this->orderMapper->map($checkout, $data, $skipped);

        if ($checkout instanceof AppliedCouponsAwareInterface) {
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
