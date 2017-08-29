<?php

namespace Oro\Bundle\PromotionBundle\Twig;

use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This extension introduces function to summarize applied discounts' amounts for same promotions.
 */
class AppliedDiscountsExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_promotion_prepare_applied_discounts', [$this, 'prepareAppliedDiscounts']),
        ];
    }

    /**
     * @param array|AppliedPromotion[] $appliedPromotions
     * @return array
     */
    public function prepareAppliedDiscounts($appliedPromotions)
    {
        $items = [];
        foreach ($appliedPromotions as $appliedPromotion) {
            $promotionId = $appliedPromotion->getId();

            $couponCode = null;
            if ($appliedPromotion->getAppliedCoupon()) {
                $couponCode = $appliedPromotion->getAppliedCoupon()->getCouponCode();
            }

            $items[$promotionId] = [
                'couponCode' => $couponCode,
                'promotionName' => $appliedPromotion->getPromotionName(),
                'promotionId' => $appliedPromotion->getSourcePromotionId(),
                'enabled' => $appliedPromotion->isActive(),
                'amount' => 0,
                'type' => $appliedPromotion->getType()
            ];

            foreach ($appliedPromotion->getAppliedDiscounts() as $appliedDiscount) {
                $items[$promotionId]['amount'] += $appliedDiscount->getAmount();
                $items[$promotionId]['currency'] = $appliedDiscount->getCurrency();
            }
        }

        return array_values($items);
    }
}
