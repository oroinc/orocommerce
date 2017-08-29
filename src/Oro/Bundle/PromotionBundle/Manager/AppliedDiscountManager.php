<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AppliedDiscountManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var AppliedPromotionMapper
     */
    protected $promotionMapper;

    /**
     * @param ContainerInterface $container
     * @param DoctrineHelper $doctrineHelper
     * @param AppliedPromotionMapper $promotionMapper
     */
    public function __construct(
        ContainerInterface $container,
        DoctrineHelper $doctrineHelper,
        AppliedPromotionMapper $promotionMapper
    ) {
        $this->container = $container;
        $this->doctrineHelper = $doctrineHelper;
        $this->promotionMapper = $promotionMapper;
    }

    /**
     * @param Order $order
     * @param bool $flush
     */
    public function saveAppliedDiscounts(Order $order, $flush = false)
    {
        $discountContext = $this->getPromotionExecutor()->execute($order);

        $manager = $this->getAppliedPromotionsManager();
        foreach ($this->createAppliedPromotions($discountContext) as $appliedPromotion) {
            $appliedPromotion->setOrder($order);
            $manager->persist($appliedPromotion);
        }

        if ($flush) {
            $manager->flush();
        }
    }

    /**
     * @param DiscountContext $discountContext
     * @return AppliedPromotion[]
     */
    private function createAppliedPromotions(DiscountContext $discountContext)
    {
        /** @var AppliedPromotion[] $appliedPromotions */
        $appliedPromotions = [];
        /**
         * @var DiscountInformation $discountInformation
         * @var OrderLineItem $orderLineItem
         */
        foreach ($this->collectDiscountsInformation($discountContext) as list($discountInformation, $orderLineItem)) {
            $promotion = $discountInformation->getDiscount()->getPromotion();
            if (empty($appliedPromotions[$promotion->getId()])) {
                $appliedPromotions[$promotion->getId()] = $this->promotionMapper
                    ->mapPromotionDataToAppliedPromotion($promotion);
            }

            $appliedDiscount = $this->createAppliedDiscount($discountInformation);
            $appliedDiscount->setLineItem($orderLineItem);
            $appliedPromotions[$promotion->getId()]->addAppliedDiscount($appliedDiscount);
        }

        return $appliedPromotions;
    }

    /**
     * @param DiscountContext $discountContext
     * @return \Generator|array
     */
    private function collectDiscountsInformation(DiscountContext $discountContext)
    {
        foreach ($discountContext->getLineItems() as $lineItem) {
            foreach ($lineItem->getDiscountsInformation() as $discountInformation) {
                yield [$discountInformation, $lineItem->getSourceLineItem()];
            }
        }

        foreach ($discountContext->getShippingDiscountsInformation() as $discountInformation) {
            yield [$discountInformation, null];
        }

        foreach ($discountContext->getSubtotalDiscountsInformation() as $discountInformation) {
            yield [$discountInformation, null];
        }
    }

    /**
     * Remove applied promotions with discounts by order
     *
     * @param Order $order
     * @param bool $flush
     */
    public function removeAppliedDiscountByOrder(Order $order, $flush = false)
    {
        $appliedPromotions = $this->getAppliedPromotionsRepository()->findByOrder($order);

        foreach ($appliedPromotions as $appliedPromotion) {
            $this->removeAppliedPromotion($appliedPromotion);
        }

        if ($flush) {
            $this->getAppliedPromotionsManager()->flush();
        }
    }

    /**
     * @param DiscountInformation $discountInformation
     * @return AppliedDiscount
     */
    private function createAppliedDiscount(DiscountInformation $discountInformation): AppliedDiscount
    {
        $appliedDiscount = new AppliedDiscount();
        $appliedDiscount->setAmount($discountInformation->getDiscountAmount());
        $appliedDiscount->setCurrency($discountInformation->getDiscount()->getDiscountCurrency());

        return $appliedDiscount;
    }

    /**
     * @return PromotionExecutor
     */
    protected function getPromotionExecutor(): PromotionExecutor
    {
        // Using DI container instead of concrete service due to circular reference
        return $this->container->get('oro_promotion.promotion_executor');
    }

    /**
     * @return EntityManager
     */
    protected function getAppliedPromotionsManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass(AppliedPromotion::class);
    }

    /**
     * @return AppliedPromotionRepository|EntityRepository
     */
    protected function getAppliedPromotionsRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(AppliedPromotion::class);
    }

    /**
     * @param AppliedPromotion $appliedPromotion
     * @param bool $flush
     * @return bool
     */
    protected function removeAppliedPromotion(AppliedPromotion $appliedPromotion, $flush = false): bool
    {
        $em = $this->getAppliedPromotionsManager();

        if (!$em->contains($appliedPromotion)) {
            return false;
        }

        $em->remove($appliedPromotion);

        if ($flush) {
            $em->flush($appliedPromotion);
        }

        return true;
    }

    /**
     * @param PromotionDataInterface $promotion
     * @return null|Promotion
     */
    protected function getManagedPromotion(PromotionDataInterface $promotion)
    {
        if ($promotion instanceof Promotion) {
            return $promotion;
        }

        return $this->doctrineHelper
            ->getEntityManagerForClass(Promotion::class)
            ->find(Promotion::class, $promotion->getId());
    }

    /**
     * @param AppliedCouponsAwareInterface $couponsHolder
     * @param PromotionDataInterface $promotion
     * @return Coupon|null
     */
    protected function getAppliedCoupon(AppliedCouponsAwareInterface $couponsHolder, PromotionDataInterface $promotion)
    {
        $appliedCouponCodes = $couponsHolder->getAppliedCoupons()->map(
            function (Coupon $coupon) {
                return $coupon->getCode();
            }
        )->toArray();

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->in('code', $appliedCouponCodes));

        $coupon = $promotion->getCoupons()->matching($criteria)->first();
        if ($coupon instanceof Coupon) {
            return $coupon;
        }

        return null;
    }
}
