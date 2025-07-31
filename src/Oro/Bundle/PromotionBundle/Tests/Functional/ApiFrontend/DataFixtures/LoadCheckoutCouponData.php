<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProviderInterface;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCheckoutCouponData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadCouponData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $data = [
            'checkout.in_progress' => [LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL],
            'checkout.completed' => [LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL]
        ];
        foreach ($data as $checkoutReference => $coupons) {
            /** @var Checkout $checkout */
            $checkout = $this->getReference($checkoutReference);
            /** @var EntityCouponsProviderInterface $entityCouponsProvider */
            $entityCouponsProvider = $this->container->get('oro_promotion.provider.entity_coupons_provider');
            foreach ($coupons as $i => $couponReference) {
                $appliedCoupon = $entityCouponsProvider->createAppliedCouponByCoupon(
                    $this->getReference($couponReference)
                );
                $checkout->addAppliedCoupon($appliedCoupon);
                $manager->persist($appliedCoupon);
                $this->setReference($checkoutReference . '.applied_coupon.' . $i, $appliedCoupon);
            }
        }
        $manager->flush();
    }
}
