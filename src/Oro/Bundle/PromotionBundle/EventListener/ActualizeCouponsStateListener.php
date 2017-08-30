<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ActualizeCouponsStateListener
{
    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param TotalCalculateBeforeEvent $event
     */
    public function onBeforeTotalCalculate(TotalCalculateBeforeEvent $event)
    {
        $entity = $event->getEntity();
        $request = $event->getRequest();

        if ($entity instanceof AppliedCouponsAwareInterface && $request->request->has('addedCouponIds')) {
            $coupons = $this->getAddedCoupons($request->request->get('addedCouponIds'));
            foreach ($coupons as $coupon) {
                $entity->addAppliedCoupon($this->createAppliedCoupon($coupon));
            }
        }
    }

    /**
     * @param string $ids
     * @return Coupon[]
     */
    private function getAddedCoupons($ids)
    {
        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->registry
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class);

        return $ids ? $couponRepository->getCouponsWithPromotionByIds(explode(',', $ids)) : [];
    }

    /**
     * @param Coupon $coupon
     * @return AppliedCoupon
     */
    private function createAppliedCoupon(Coupon $coupon)
    {
        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon
            ->setCouponCode($coupon->getCode())
            ->setSourceCouponId($coupon->getId())
            ->setSourcePromotionId($coupon->getPromotion()->getId());

        return $appliedCoupon;
    }
}
