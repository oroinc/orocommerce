<?php

namespace Oro\Bundle\PromotionBundle\Twig;

use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
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
     * @param array|AppliedDiscount[] $appliedDiscounts
     * @return array
     */
    public function prepareAppliedDiscounts($appliedDiscounts)
    {
        $items = [];
        foreach ($appliedDiscounts as $appliedDiscount) {
            $this->addDiscountInformationToItems($appliedDiscount, $items);
        }

        return array_values($items);
    }

    /**
     * @param AppliedDiscount $appliedDiscount
     * @param array $items
     */
    private function addDiscountInformationToItems(AppliedDiscount $appliedDiscount, array &$items)
    {
        $sourcePromotionId = $appliedDiscount->getSourcePromotionId();
        if (!isset($items[$sourcePromotionId])) {
            $items[$sourcePromotionId] = [
                'couponCode' => $appliedDiscount->getCouponCode(),
                'promotionName' => $appliedDiscount->getPromotionName(),
                'promotionId' => $sourcePromotionId,
                'enabled' => $appliedDiscount->isEnabled(),
                'amount' => 0,
                'currency' => $appliedDiscount->getCurrency(),
                'type' => $appliedDiscount->getType()
            ];
        }

        $items[$sourcePromotionId]['amount'] += $appliedDiscount->getAmount();
    }
}
