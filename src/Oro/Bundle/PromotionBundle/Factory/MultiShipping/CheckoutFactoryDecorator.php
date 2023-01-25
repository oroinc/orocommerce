<?php

namespace Oro\Bundle\PromotionBundle\Factory\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;

/**
 * Apply coupons for new checkout from checkout source.
 */
class CheckoutFactoryDecorator implements CheckoutFactoryInterface
{
    private CheckoutFactoryInterface $checkoutFactory;

    public function __construct(CheckoutFactoryInterface $checkoutFactory)
    {
        $this->checkoutFactory = $checkoutFactory;
    }

    public function createCheckout(Checkout $checkoutSource, iterable $lineItems): Checkout
    {
        /** @var AppliedCouponsAwareInterface $order */
        $checkout = $this->checkoutFactory->createCheckout($checkoutSource, $lineItems);

        if ($checkoutSource instanceof AppliedCouponsAwareInterface) {
            foreach ($checkoutSource->getAppliedCoupons() as $appliedCoupon) {
                $checkout->addAppliedCoupon($this->getAppliedCouponCopy($appliedCoupon));
            }
        }

        return $checkout;
    }

    private function getAppliedCouponCopy(AppliedCoupon $appliedCoupon): AppliedCoupon
    {
        return (new AppliedCoupon())
            ->setSourceCouponId($appliedCoupon->getSourceCouponId())
            ->setSourcePromotionId($appliedCoupon->getSourcePromotionId())
            ->setCouponCode($appliedCoupon->getCouponCode());
    }
}
