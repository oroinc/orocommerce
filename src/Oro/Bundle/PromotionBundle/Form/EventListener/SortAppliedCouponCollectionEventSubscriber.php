<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Event subscriber that sorts the submitted applied coupons collection by promotion sort order.
 */
class SortAppliedCouponCollectionEventSubscriber implements EventSubscriberInterface
{
    private const int BEFORE_RESIZE_LISTENER_PRIORITY = 300;

    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => [
                'onPreSetData',
                // Must run before ResizeFormListener::preSetData (priority 0) to ensure
                // child forms are created in the correct order by ResizeFormListener.
                self::BEFORE_RESIZE_LISTENER_PRIORITY,
            ],
            FormEvents::PRE_SUBMIT => [
                'onPreSubmit',
                // Must run before ResizeFormListener::preSetData (priority 0) to ensure
                // child forms are created in the correct order by ResizeFormListener.
                self::BEFORE_RESIZE_LISTENER_PRIORITY,
            ],
        ];
    }

    public function onPreSetData(FormEvent $event): void
    {
        $appliedCoupons = $event->getData();
        if (!$appliedCoupons instanceof Collection || $appliedCoupons->isEmpty()) {
            return;
        }

        $promotionSortOrders = $this->getPromotionsSortOrders([], $appliedCoupons);

        /** @var array<int, AppliedCoupon> $appliedCouponsArray */
        $appliedCouponsArray = $appliedCoupons->toArray();
        uasort($appliedCouponsArray, static function ($a, $b) use ($promotionSortOrders) {
            $aId = $a->getSourcePromotionId() ?? 0;
            $bId = $b->getSourcePromotionId() ?? 0;
            $aSortOrder = $promotionSortOrders[$aId] ?? 0;
            $bSortOrder = $promotionSortOrders[$bId] ?? 0;

            return $aSortOrder <=> $bSortOrder;
        });

        $event->setData(new ArrayCollection($appliedCouponsArray));
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $submittedData = $event->getData();
        $form = $event->getForm();
        $appliedCoupons = $form->getData();
        if (!$appliedCoupons instanceof Collection) {
            $appliedCoupons = new ArrayCollection();
        }

        if (!$submittedData || !is_array($submittedData)) {
            return;
        }

        $promotionSortOrders = $this->getPromotionsSortOrders($submittedData, $appliedCoupons);

        uasort($submittedData, static function ($a, $b) use ($promotionSortOrders) {
            $aId = isset($a['sourcePromotionId']) ? (int)$a['sourcePromotionId'] : 0;
            $bId = isset($b['sourcePromotionId']) ? (int)$b['sourcePromotionId'] : 0;
            $aId = max($aId, 0);
            $bId = max($bId, 0);
            $aSortOrder = $promotionSortOrders[$aId] ?? 0;
            $bSortOrder = $promotionSortOrders[$bId] ?? 0;

            return $aSortOrder <=> $bSortOrder;
        });

        $event->setData($submittedData);
    }

    /**
     * @param array $submittedData
     * @param Collection<int, AppliedCoupon> $appliedCoupons
     *
     * @return array<int,int> [promotionId => sortOrder]
     */
    private function getPromotionsSortOrders(array $submittedData, Collection $appliedCoupons): array
    {
        $promotionSortOrders = [];
        foreach ($appliedCoupons as $appliedCoupon) {
            if ($appliedCoupon instanceof AppliedCoupon) {
                $appliedPromotion = $appliedCoupon->getAppliedPromotion();
                $promotionData = $appliedPromotion ? $appliedPromotion->getPromotionData() : null;
                $sourcePromotionId = $appliedCoupon->getSourcePromotionId();
                if ($sourcePromotionId === null) {
                    continue;
                }

                $promotionSortOrders[$sourcePromotionId] = $promotionData['rule']['sortOrder'] ?? 0;
            }
        }

        $submittedPromotionIds = $this->getSubmittedPromotionIds($submittedData);
        $submittedPromotionIds = array_diff_key($submittedPromotionIds, $promotionSortOrders);

        if ($submittedPromotionIds) {
            $promotionRepository = $this->managerRegistry->getRepository(Promotion::class);
            $promotions = $promotionRepository->findBy(['id' => array_values($submittedPromotionIds)]);

            foreach ($promotions as $promotion) {
                $promotionSortOrders[$promotion->getId()] = (int)($promotion->getRule()?->getSortOrder() ?? 0);
            }
        }

        return $promotionSortOrders;
    }

    /**
     * @param array $submittedData
     *
     * @return array<int,int> promotion id => promotion id
     */
    private function getSubmittedPromotionIds(array $submittedData): array
    {
        $submittedPromotionIds = [];
        foreach ($submittedData as $appliedCouponData) {
            if (!isset($appliedCouponData['sourcePromotionId'])) {
                continue;
            }

            $sourcePromotionId = (int)$appliedCouponData['sourcePromotionId'];
            if ($sourcePromotionId <= 0) {
                continue;
            }

            $submittedPromotionIds[$sourcePromotionId] = $sourcePromotionId;
        }

        return $submittedPromotionIds;
    }
}
