<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Component\DependencyInjection\ServiceLink;

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
     * @param bool $removeOldPromotions
     */
    public function createAppliedPromotions(Order $order, $removeOldPromotions = false)
    {
        $discountContext = $this->getPromotionExecutor()->execute($order);
        $appliedPromotions = $this->prepareAppliedPromotions($discountContext, $order);

        // Actualize applied promotions state for order
        $order->getAppliedPromotions()->clear();
        foreach ($appliedPromotions as $appliedPromotion) {
            $order->addAppliedPromotion($appliedPromotion);
        }

        if ($removeOldPromotions) {
            $this->getAppliedPromotionsRepository()->removeAppliedPromotionsByOrder($order);
        }
    }

    /**
     * @param DiscountContextInterface $discountContext
     * @param Order $order
     * @return AppliedPromotion[]
     */
    private function prepareAppliedPromotions(DiscountContextInterface $discountContext, Order $order)
    {
        $appliedPromotions = [];
        $manager = $this->getAppliedPromotionsManager();

        /**
         * @var DiscountInformation $discountInformation
         * @var OrderLineItem $orderLineItem
         */
        foreach ($this->collectDiscountsInformation($discountContext) as list($discountInformation, $orderLineItem)) {
            $promotion = $discountInformation->getDiscount()->getPromotion();
            if (empty($appliedPromotions[$promotion->getId()])) {
                $appliedPromotion = new AppliedPromotion();
                $this->promotionMapper->mapPromotionDataToAppliedPromotion($appliedPromotion, $promotion, $order);
                if ($discountInformation->getDiscount() instanceof DisabledDiscountDecorator) {
                    $appliedPromotion->setActive(false);
                }

                $manager->persist($appliedPromotion);
                $appliedPromotions[$promotion->getId()] = $appliedPromotion;
            }

            $appliedDiscount = $this->createAppliedDiscount($discountInformation);
            $appliedDiscount->setLineItem($orderLineItem);
            $appliedPromotions[$promotion->getId()]->addAppliedDiscount($appliedDiscount);
        }

        return $appliedPromotions;
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
     * @return AppliedDiscount
     */
    private function createAppliedDiscount(DiscountInformation $discountInformation): AppliedDiscount
    {
        $appliedDiscount = new AppliedDiscount();
        $appliedDiscount->setAmount($discountInformation->getDiscountAmount());
        $appliedDiscount->setCurrency($discountInformation->getDiscount()->getDiscountCurrency());

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
     * @return AppliedPromotionRepository|EntityRepository
     */
    protected function getAppliedPromotionsRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(AppliedPromotion::class);
    }
}
