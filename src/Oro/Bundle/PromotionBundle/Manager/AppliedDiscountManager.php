<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;

class AppliedDiscountManager
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * Using service container instead of concrete class due circular reference
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Order $order
     * @return AppliedDiscount[]
     */
    public function createAppliedDiscounts(Order $order)
    {
        $discountContext = $this->getPromotionExecutor()->execute($order);

        $appliedDiscounts = [];
        foreach ($discountContext->getSubtotalDiscountsInformation() as $subtotalDiscountInfo) {
            $appliedDiscounts[] = $this->createAppliedDiscount($order, $subtotalDiscountInfo);
        }
        foreach ($discountContext->getShippingDiscountsInformation() as $shippingDiscountInfo) {
            $appliedDiscounts[] = $this->createAppliedDiscount($order, $shippingDiscountInfo);
        }
        foreach ($discountContext->getLineItems() as $discountLineItem) {
            $options = [AppliedDiscount::OPTION_LINE_ITEM_ID => $discountLineItem->getSourceLineItem()->getId()];
            foreach ($discountLineItem->getDiscountsInformation() as $discountInfo) {
                $appliedDiscounts[] = $this->createAppliedDiscount($order, $discountInfo, $options);
            }
        }
        return $appliedDiscounts;
    }

    /**
     * @param Order $order
     * @param DiscountInformation $discountInfo
     * @param array $options
     * @return AppliedDiscount
     */
    protected function createAppliedDiscount(
        Order $order,
        DiscountInformation $discountInfo,
        array $options = []
    ): AppliedDiscount {

        $discount = $discountInfo->getDiscount();
        $promotion = $discount->getPromotion();
        if (!$promotion) {
            throw new \LogicException('required parameter "promotion" of discount is missing');
        }

        $options[AppliedDiscount::OPTION_CLASS] = get_class($discount);
        return (new AppliedDiscount())
            ->setOrder($order)
            ->setType($discount->getDiscountType())
            ->setAmount($discountInfo->getDiscountAmount())
            ->setCurrency($discount->getDiscountCurrency() ?? $order->getCurrency())
            ->setConfigOptions($promotion->getDiscountConfiguration()->getOptions())
            ->setOptions($options)
            ->setPromotion($promotion);
    }

    /**
     * @return PromotionExecutor
     */
    protected function getPromotionExecutor(): PromotionExecutor
    {
        return $this->container->get('oro_promotion.promotion_executor');
    }
}
