<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProviderInterface;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadCheckoutWithAppliedCouponData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const PROMOTION_CHECKOUT_1 = LoadCheckoutData::PROMOTION_CHECKOUT_1;
    public const PROMOTION_CHECKOUT_1_COUPON_1 = self::PROMOTION_CHECKOUT_1 . '.applied_coupon_1';
    public const PROMOTION_CHECKOUT_1_COUPON_2 = self::PROMOTION_CHECKOUT_1 . '.applied_coupon_2';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadCheckoutData::class, LoadCouponData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var EntityCouponsProviderInterface $entityCouponsProvider */
        $entityCouponsProvider = $this->container->get('oro_promotion.provider.entity_coupons_provider');
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);
        $appliedCoupon1 = $this->applyCoupon(
            $manager,
            $checkout,
            $this->getReference(LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL),
            $entityCouponsProvider
        );
        $this->setReference(self::PROMOTION_CHECKOUT_1_COUPON_1, $appliedCoupon1);
        $appliedCoupon2 = $this->applyCoupon(
            $manager,
            $checkout,
            $this->getReference(LoadCouponData::COUPON_DISABLED),
            $entityCouponsProvider
        );
        $this->setReference(self::PROMOTION_CHECKOUT_1_COUPON_2, $appliedCoupon2);
        $manager->flush();
    }

    private function applyCoupon(
        ObjectManager $manager,
        Checkout $checkout,
        Coupon $coupon,
        EntityCouponsProviderInterface $entityCouponsProvider
    ): AppliedCoupon {
        $appliedCoupon = $entityCouponsProvider->createAppliedCouponByCoupon($coupon);
        $checkout->addAppliedCoupon($appliedCoupon);
        $manager->persist($appliedCoupon);

        return $appliedCoupon;
    }
}
