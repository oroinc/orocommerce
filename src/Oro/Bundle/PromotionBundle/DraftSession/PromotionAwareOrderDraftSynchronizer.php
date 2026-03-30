<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\DraftSession;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Synchronizes applied promotions, coupons, and discounts between source and target order.
 */
class PromotionAwareOrderDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EntityDraftFactoryInterface $entityDraftFactory,
        private readonly EntityDraftSynchronizerInterface $entityDraftSynchronizer,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $entityClass === Order::class;
    }

    #[\Override]
    public function synchronizeFromDraft(
        EntityDraftAwareInterface $draft,
        EntityDraftAwareInterface $entity,
    ): void {
        assert($draft instanceof Order);
        assert($entity instanceof Order);

        $this->synchronizePromotions($draft, $entity);
    }

    #[\Override]
    public function synchronizeToDraft(
        EntityDraftAwareInterface $entity,
        EntityDraftAwareInterface $draft,
    ): void {
        assert($entity instanceof Order);
        assert($draft instanceof Order);

        $this->synchronizePromotions($entity, $draft);
    }

    private function synchronizePromotions(Order $sourceOrder, Order $targetOrder): void
    {
        /** @var array<int, AppliedPromotion> $sourceAppliedPromotionMap */
        $sourceAppliedPromotionMap = [];
        foreach ($sourceOrder->getAppliedPromotions() as $sourceAppliedPromotion) {
            $sourceAppliedPromotionMap[$sourceAppliedPromotion->getSourcePromotionId()] = $sourceAppliedPromotion;
        }

        /** @var array<int,AppliedPromotion> $targetAppliedPromotionMap */
        $targetAppliedPromotionMap = [];
        foreach ($targetOrder->getAppliedPromotions() as $targetAppliedPromotion) {
            $targetAppliedPromotionMap[$targetAppliedPromotion->getSourcePromotionId()] = $targetAppliedPromotion;
        }

        $entityManager = $this->doctrine->getManagerForClass(AppliedPromotion::class);

        foreach ($targetAppliedPromotionMap as $promotionId => $targetAppliedPromotion) {
            if (!isset($sourceAppliedPromotionMap[$promotionId])) {
                $targetOrder->removeAppliedPromotion($targetAppliedPromotion);

                $targetAppliedCoupon = $targetAppliedPromotion->getAppliedCoupon();
                if ($targetAppliedCoupon) {
                    $targetOrder->removeAppliedCoupon($targetAppliedCoupon);
                    $entityManager->remove($targetAppliedCoupon);
                }

                $entityManager->remove($targetAppliedPromotion);
            }
        }

        foreach ($sourceAppliedPromotionMap as $promotionId => $sourceAppliedPromotion) {
            $targetAppliedPromotion = $targetAppliedPromotionMap[$promotionId] ??
                $this->createSameInstance($sourceAppliedPromotion);
            $this->syncAppliedPromotion($sourceAppliedPromotion, $targetAppliedPromotion, $targetOrder);

            if (!$targetOrder->getAppliedPromotions()->contains($targetAppliedPromotion)) {
                $targetOrder->addAppliedPromotion($targetAppliedPromotion);
            }

            $entityManager->persist($targetAppliedPromotion);
        }
    }

    private function syncAppliedPromotion(
        AppliedPromotion $sourceAppliedPromotion,
        AppliedPromotion $targetAppliedPromotion,
        Order $targetOrder
    ): void {
        $targetAppliedPromotion->setActive($sourceAppliedPromotion->isActive());
        $targetAppliedPromotion->setRemoved($sourceAppliedPromotion->isRemoved());
        $targetAppliedPromotion->setType($sourceAppliedPromotion->getType());
        $targetAppliedPromotion->setSourcePromotionId($sourceAppliedPromotion->getSourcePromotionId());
        $targetAppliedPromotion->setPromotionName($sourceAppliedPromotion->getPromotionName());
        $targetAppliedPromotion->setConfigOptions($sourceAppliedPromotion->getConfigOptions());
        $targetAppliedPromotion->setPromotionData($sourceAppliedPromotion->getPromotionData());
        $targetAppliedPromotion->setOrder($targetOrder);

        $this->syncAppliedDiscounts($sourceAppliedPromotion, $targetAppliedPromotion, $targetOrder);

        $entityManager = $this->doctrine->getManagerForClass(AppliedCoupon::class);

        $appliedCoupon = $sourceAppliedPromotion->getAppliedCoupon();
        if ($appliedCoupon) {
            $targetAppliedCoupon = $targetAppliedPromotion->getAppliedCoupon() ??
                $this->createSameInstance($appliedCoupon);
            $this->syncAppliedCoupon($appliedCoupon, $targetAppliedCoupon, $targetOrder);

            $targetAppliedPromotion->setAppliedCoupon($targetAppliedCoupon);
            if (!$targetOrder->getAppliedCoupons()->contains($targetAppliedCoupon)) {
                $targetOrder->addAppliedCoupon($targetAppliedCoupon);
            }

            $entityManager->persist($targetAppliedCoupon);
        } elseif ($targetAppliedPromotion->getAppliedCoupon()) {
            $couponToRemove = $targetAppliedPromotion->getAppliedCoupon();
            $targetAppliedPromotion->setAppliedCoupon(null);
            $targetOrder->removeAppliedCoupon($couponToRemove);

            // Explicitly removes the applied coupon as removing it from the collection is not enough if
            // the coupon is a new entity scheduled for persistence.
            $entityManager->remove($couponToRemove);
        }
    }

    private function syncAppliedDiscounts(
        AppliedPromotion $sourceAppliedPromotion,
        AppliedPromotion $targetAppliedPromotion,
        Order $targetOrder
    ): void {
        $entityManager = $this->doctrine->getManagerForClass(AppliedDiscount::class);

        foreach ($targetAppliedPromotion->getAppliedDiscounts() as $targetAppliedDiscount) {
            $targetAppliedPromotion->removeAppliedDiscount($targetAppliedDiscount);
            $entityManager->remove($targetAppliedDiscount);
        }

        foreach ($sourceAppliedPromotion->getAppliedDiscounts() as $sourceAppliedDiscount) {
            $clonedAppliedDiscount = $this->cloneAppliedDiscount($sourceAppliedDiscount, $targetOrder);

            $targetAppliedPromotion->addAppliedDiscount($clonedAppliedDiscount);
            $entityManager->persist($clonedAppliedDiscount);
        }
    }

    private function cloneAppliedDiscount(AppliedDiscount $sourceDiscount, Order $targetOrder): AppliedDiscount
    {
        $clonedDiscount = new AppliedDiscount();
        $clonedDiscount->setAmount((float)$sourceDiscount->getAmount());
        $clonedDiscount->setCurrency($sourceDiscount->getCurrency());

        if ($sourceDiscount->getLineItem()) {
            $targetLineItem = $this->findOrCreateTargetLineItem($sourceDiscount->getLineItem(), $targetOrder);
            $clonedDiscount->setLineItem($targetLineItem);
        }

        return $clonedDiscount;
    }

    private function findOrCreateTargetLineItem(OrderLineItem $sourceLineItem, Order $targetOrder): OrderLineItem
    {
        $entityManager = $this->doctrine->getManagerForClass(OrderLineItem::class);

        // Finds or creates the target line item when the source line item is draft.
        if ($sourceLineItem->getDraftSessionUuid()) {
            $targetLineItem = $sourceLineItem->getDraftSource();
            if ($targetLineItem !== null) {
                return $targetLineItem;
            }

            // If draft source is null, then the line item is new and should be created based on the line item draft.
            $targetLineItem = new OrderLineItem();
            $this->entityDraftSynchronizer->synchronizeFromDraft($sourceLineItem, $targetLineItem);

            $targetOrder->addLineItem($targetLineItem);

            $entityManager->persist($targetLineItem);

            return $targetLineItem;
        }

        // Finds or creates the target line item when the source line item is not draft.
        foreach ($targetOrder->getLineItems() as $targetLineItem) {
            if ($sourceLineItem->getId() && $targetLineItem->getDraftSource()?->getId() === $sourceLineItem->getId()) {
                // Draft is found.
                return $targetLineItem;
            }

            if (!$sourceLineItem->getId()) {
                // New line item may have a reference to its draft.
                $draftLineItem = $sourceLineItem->getDrafts()->first() ?: null;
                if ($draftLineItem?->getId() === $targetLineItem->getId()) {
                    // Newly created draft is found.
                    return $targetLineItem;
                }
            }
        }

        // Creates a new order line item draft.

        /** @var OrderLineItem $targetLineItem */
        $targetLineItem = $this->entityDraftFactory->createDraft($sourceLineItem, $targetOrder->getDraftSessionUuid());

        $targetOrder->addLineItem($targetLineItem);

        $entityManager->persist($targetLineItem);

        return $targetLineItem;
    }

    private function syncAppliedCoupon(
        AppliedCoupon $sourceCoupon,
        AppliedCoupon $targetCoupon,
        Order $targetOrder
    ): void {
        $targetCoupon->setCouponCode($sourceCoupon->getCouponCode());
        $targetCoupon->setSourcePromotionId($sourceCoupon->getSourcePromotionId());
        $targetCoupon->setSourceCouponId($sourceCoupon->getSourceCouponId());
        $targetCoupon->setOrder($targetOrder);
    }

    /**
     * Creates an instance of the same class as the specified entity is.
     */
    private function createSameInstance(object $object): object
    {
        return new (ClassUtils::getClass($object));
    }
}
