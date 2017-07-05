<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
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
            foreach ($discountLineItem->getDiscountsInformation() as $discountInfo) {
                $appliedDiscount = $this->createAppliedDiscount($order, $discountInfo);
                $lineItem = $discountLineItem->getSourceLineItem();
                if ($lineItem instanceof OrderLineItem) {
                    $appliedDiscount->setLineItem($lineItem);
                }
                $appliedDiscounts[] = $appliedDiscount;
            }
        }
        return $appliedDiscounts;
    }

    /**
     * @param Order $order
     * @param DiscountInformation $discountInfo
     * @return AppliedDiscount
     */
    protected function createAppliedDiscount(Order $order, DiscountInformation $discountInfo): AppliedDiscount
    {
        $discount = $discountInfo->getDiscount();
        $promotion = $discount->getPromotion();
        if (!$promotion) {
            throw new \LogicException('required parameter "promotion" of discount is missing');
        }
        return (new AppliedDiscount())
            ->setOrder($order)
            ->setClass(get_class($discount))
            ->setAmount($discountInfo->getDiscountAmount())
            ->setCurrency($order->getCurrency())
            ->setConfigOptions($promotion->getDiscountConfiguration()->getOptions())
            ->setPromotion($promotion)
            ->setPromotionName($promotion->getRule()->getName());
    }

    /**
     * @return PromotionExecutor
     */
    protected function getPromotionExecutor(): PromotionExecutor
    {
        return $this->container->get('oro_promotion.promotion_executor');
    }
}
