<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProvider;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ActualizeCouponsStateListener
{
    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var EntityCouponsProvider
     */
    private $entityCouponsProvider;

    /**
     * @param RegistryInterface $registry
     * @param EntityCouponsProvider $entityCouponsProvider
     */
    public function __construct(RegistryInterface $registry, EntityCouponsProvider $entityCouponsProvider)
    {
        $this->registry = $registry;
        $this->entityCouponsProvider = $entityCouponsProvider;
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
                $entity->addAppliedCoupon($this->entityCouponsProvider->createAppliedCouponByCoupon($coupon));
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
}
