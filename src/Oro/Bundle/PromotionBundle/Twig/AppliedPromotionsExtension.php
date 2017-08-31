<?php

namespace Oro\Bundle\PromotionBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;

/**
 * This extension introduces function to get promotions' information for displaying as a table.
 */
class AppliedPromotionsExtension extends \Twig_Extension
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_promotion_prepare_applied_promotions_info',
                [$this, 'prepareAppliedPromotionsInfo']
            ),
            new \Twig_SimpleFunction('oro_promotion_get_applied_promotions_info', [$this, 'getAppliedPromotionsInfo'])
        ];
    }

    /**
     * @param Collection|AppliedPromotion[] $appliedPromotions
     * @return array
     */
    public function prepareAppliedPromotionsInfo($appliedPromotions): array
    {
        $items = [];
        foreach ($appliedPromotions as $appliedPromotion) {
            $couponCode = null;
            $sourceCouponId = null;
            if ($appliedPromotion->getAppliedCoupon()) {
                $couponCode = $appliedPromotion->getAppliedCoupon()->getCouponCode();
                $sourceCouponId = $appliedPromotion->getAppliedCoupon()->getSourceCouponId();
            }

            $item = [
                'couponCode' => $couponCode,
                'promotionName' => $appliedPromotion->getPromotionName(),
                'promotionId' => $appliedPromotion->getSourcePromotionId(),
                'active' => $appliedPromotion->isActive(),
                'amount' => 0,
                'type' => $appliedPromotion->getType(),
                'sourcePromotionId' => $appliedPromotion->getSourcePromotionId(),
                'sourceCouponId' => $sourceCouponId
            ];

            foreach ($appliedPromotion->getAppliedDiscounts() as $appliedDiscount) {
                $item['amount'] += $appliedDiscount->getAmount();
                $item['currency'] = $appliedDiscount->getCurrency();
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getAppliedPromotionsInfo(Order $order): array
    {
        /** @var AppliedPromotionRepository $repository */
        $repository = $this->registry
            ->getManagerForClass(AppliedPromotion::class)
            ->getRepository(AppliedPromotion::class);

        return $repository->getAppliedPromotionsInfo($order);
    }
}
