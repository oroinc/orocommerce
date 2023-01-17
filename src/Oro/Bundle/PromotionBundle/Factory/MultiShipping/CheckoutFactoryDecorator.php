<?php

namespace Oro\Bundle\PromotionBundle\Factory\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;

/**
 * Applies coupons for a created checkout from a specific checkout.
 */
class CheckoutFactoryDecorator implements CheckoutFactoryInterface
{
    private CheckoutFactoryInterface $checkoutFactory;

    public function __construct(CheckoutFactoryInterface $checkoutFactory)
    {
        $this->checkoutFactory = $checkoutFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createCheckout(Checkout $source, iterable $lineItems): Checkout
    {
        $checkout = $this->checkoutFactory->createCheckout($source, $lineItems);
        if ($source instanceof AppliedCouponsAwareInterface) {
            $appliedCoupons = $source->getAppliedCoupons();
            foreach ($appliedCoupons as $appliedCoupon) {
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
