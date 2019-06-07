<?php

namespace Oro\Bundle\PromotionBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to retrieve information about applied promotions:
 *   - oro_promotion_prepare_applied_promotions_info
 *   - oro_promotion_get_applied_promotions_info
 */
class AppliedPromotionsExtension extends AbstractExtension
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
            new TwigFunction(
                'oro_promotion_prepare_applied_promotions_info',
                [$this, 'prepareAppliedPromotionsInfo']
            ),
            new TwigFunction('oro_promotion_get_applied_promotions_info', [$this, 'getAppliedPromotionsInfo'])
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
                'id' => $appliedPromotion->getId(),
                'couponCode' => $couponCode,
                'promotionName' => $appliedPromotion->getPromotionName(),
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

        usort($items, function (array $a, array $b) {
            if (empty($a['id']) && empty($b['id'])) {
                return 0;
            }

            if (empty($a['id'])) {
                return 1;
            }

            if (empty($b['id'])) {
                return -1;
            }

            return (int)$a['id'] - (int)$b['id'];
        });

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
