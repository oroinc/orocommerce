<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Manager provides useful methods to work with already applied promotions to Order entity.
 */
class AppliedPromotionManager
{
    /**
     * @var ServiceLink
     */
    protected $promotionExecutorServiceLink;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var AppliedPromotionMapper
     */
    protected $promotionMapper;

    /**
     * @var PromotionExecutor
     */
    protected $promotionExecutor;

    /**
     * @param ServiceLink $promotionExecutorServiceLink
     * @param DoctrineHelper $doctrineHelper
     * @param AppliedPromotionMapper $promotionMapper
     */
    public function __construct(
        ServiceLink $promotionExecutorServiceLink,
        DoctrineHelper $doctrineHelper,
        AppliedPromotionMapper $promotionMapper
    ) {
        $this->promotionExecutorServiceLink = $promotionExecutorServiceLink;
        $this->doctrineHelper = $doctrineHelper;
        $this->promotionMapper = $promotionMapper;
    }

    /**
     * @param Order|AppliedPromotionsAwareInterface $order
     * @param bool $removeOrphans
     */
    public function createAppliedPromotions(Order $order, $removeOrphans = false)
    {
        if (!$this->getPromotionExecutor()->supports($order)) {
            return;
        }

        $discountContext = $this->getPromotionExecutor()->execute($order);

        $appliedPromotions = $this->updateAppliedPromotions($discountContext, $order);
        $this->removeUnusedAppliedCoupons($order, $appliedPromotions);

        if ($removeOrphans) {
            $this->removeAppliedPromotionOrphans($order->getAppliedPromotions());
        }
    }

    /**
     * @param Collection|PersistentCollection $appliedPromotionsCollection
     */
    private function removeAppliedPromotionOrphans(Collection $appliedPromotionsCollection)
    {
        $manager = $this->getAppliedPromotionsManager();

        foreach ($appliedPromotionsCollection->getDeleteDiff() as $appliedPromotion) {
            $manager->remove($appliedPromotion);
        }
    }

    /**
     * @param DiscountContextInterface $discountContext
     * @param Order|AppliedPromotionsAwareInterface $order
     * @return AppliedPromotion[]
     */
    private function updateAppliedPromotions(DiscountContextInterface $discountContext, Order $order)
    {
        $appliedPromotions = [];
        $manager = $this->getAppliedPromotionsManager();

        $promotionIds = [];
        foreach ($this->collectDiscountsInformation($discountContext) as list($discountInformation, $orderLineItem)) {
            $promotionIds[$discountInformation->getDiscount()->getPromotion()->getId()] = true;
        }

        /** @var Collection $appliedPromotionsCollection */
        $appliedPromotionsCollection = $order->getAppliedPromotions();
        /** @var AppliedPromotion $appliedPromotion */
        foreach ($appliedPromotionsCollection->toArray() as $appliedPromotion) {
            if (!isset($promotionIds[$appliedPromotion->getSourcePromotionId()])) {
                $appliedPromotionsCollection->removeElement($appliedPromotion);
            }
        }

        /**
         * @var DiscountInformation $discountInformation
         * @var OrderLineItem $orderLineItem
         */
        foreach ($this->collectDiscountsInformation($discountContext) as list($discountInformation, $orderLineItem)) {
            $promotion = $discountInformation->getDiscount()->getPromotion();
            if (empty($appliedPromotions[$promotion->getId()])) {
                $appliedPromotion = $this->findOrCreateAppliedPromotion($order, $promotion);
                if (!$appliedPromotionsCollection->contains($appliedPromotion)) {
                    $appliedPromotionsCollection->add($appliedPromotion);
                }

                $this->promotionMapper->mapPromotionDataToAppliedPromotion($appliedPromotion, $promotion, $order);
                if ($discountInformation->getDiscount() instanceof DisabledDiscountDecorator) {
                    $appliedPromotion->setActive(false);
                }

                $manager->persist($appliedPromotion);
                $appliedPromotions[$promotion->getId()] = $appliedPromotion;
            }

            $appliedDiscount = $this->createAppliedDiscount($discountInformation, $order);
            $appliedDiscount->setLineItem($orderLineItem);
            $manager->persist($appliedDiscount);
            $appliedPromotions[$promotion->getId()]->addAppliedDiscount($appliedDiscount);
        }

        return $appliedPromotions;
    }

    /**
     * @param Order|AppliedPromotionsAwareInterface|AppliedCouponsAwareInterface $order
     * @param array|AppliedPromotion[] $appliedPromotions
     */
    private function removeUnusedAppliedCoupons(Order $order, array $appliedPromotions)
    {
        /** @var Collection $couponsCollection */
        $couponsCollection = $order->getAppliedCoupons();

        foreach ($couponsCollection as $appliedCoupon) {
            $isUsed = false;
            foreach ($appliedPromotions as $appliedPromotion) {
                if ($appliedPromotion->getAppliedCoupon() === $appliedCoupon) {
                    $isUsed = true;
                    break;
                }
            }

            if (!$isUsed) {
                $couponsCollection->removeElement($appliedCoupon);
            }
        }
    }

    /**
     * @param Order|AppliedPromotionsAwareInterface $order
     * @param PromotionDataInterface $promotion
     * @return AppliedPromotion
     */
    private function findOrCreateAppliedPromotion(Order $order, PromotionDataInterface $promotion)
    {
        /** @var Collection|AppliedPromotion[] $appliedPromotionsCollection */
        $appliedPromotionsCollection = $order->getAppliedPromotions();
        foreach ($appliedPromotionsCollection as $appliedPromotion) {
            if ($appliedPromotion->getSourcePromotionId() === $promotion->getId()) {
                $appliedPromotion->getAppliedDiscounts()->clear();

                return $appliedPromotion;
            }
        }

        return new AppliedPromotion();
    }

    /**
     * @param DiscountContextInterface $discountContext
     * @return \Generator|array
     */
    private function collectDiscountsInformation(DiscountContextInterface $discountContext)
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
     * @param DiscountInformation $discountInformation
     * @param Order $order
     * @return AppliedDiscount
     */
    private function createAppliedDiscount(DiscountInformation $discountInformation, Order $order): AppliedDiscount
    {
        $appliedDiscount = new AppliedDiscount();
        $appliedDiscount->setAmount($discountInformation->getDiscountAmount());
        $appliedDiscount->setCurrency($order->getCurrency());

        return $appliedDiscount;
    }

    /**
     * @return PromotionExecutor
     */
    protected function getPromotionExecutor(): PromotionExecutor
    {
        if (!$this->promotionExecutor) {
            $this->promotionExecutor = $this->promotionExecutorServiceLink->getService();
        }

        return $this->promotionExecutor;
    }

    /**
     * @return EntityManager
     */
    protected function getAppliedPromotionsManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass(AppliedPromotion::class);
    }

    /**
     * @return EntityManager
     */
    protected function getAppliedCouponsManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass(AppliedCoupon::class);
    }
}
