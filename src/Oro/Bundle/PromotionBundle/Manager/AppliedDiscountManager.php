<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;

class AppliedDiscountManager
{
    /** @var ContainerInterface */
    protected $container;

    /**
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

        $discountConfiguration = $promotion->getDiscountConfiguration();
        $discountType = $discountConfiguration->getType();
        $discountConfigurationOptions = $discountConfiguration->getOptions();

        return (new AppliedDiscount())
            ->setOrder($order)
            ->setType($discountType)
            ->setAmount($discountInfo->getDiscountAmount())
            ->setCurrency($order->getCurrency())
            ->setConfigOptions($discountConfigurationOptions)
            ->setPromotion($promotion)
            ->setPromotionName($promotion->getRule()->getName());
    }

    /**
     * @return PromotionExecutor
     */
    protected function getPromotionExecutor(): PromotionExecutor
    {
        // Using DI container instead of concrete service due to circular reference
        return $this->container->get('oro_promotion.promotion_executor');
    }
}
