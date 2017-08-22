<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedDiscountRepository;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AppliedDiscountManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var NormalizerInterface
     */
    private $promotionNormalizer;

    /**
     * @param ContainerInterface $container
     * @param DoctrineHelper $doctrineHelper
     * @param NormalizerInterface $promotionNormalizer
     */
    public function __construct(
        ContainerInterface $container,
        DoctrineHelper $doctrineHelper,
        NormalizerInterface $promotionNormalizer
    ) {
        $this->container = $container;
        $this->doctrineHelper = $doctrineHelper;
        $this->promotionNormalizer = $promotionNormalizer;
    }

    /**
     * @param Order $order
     * @param bool $flush
     * @return AppliedDiscount[]
     */
    public function saveAppliedDiscounts(Order $order, $flush = false)
    {
        $discountContext = $this->getPromotionExecutor()->execute($order);

        $appliedDiscounts = array_merge(
            $this->createSubtotalDiscounts($order, $discountContext),
            $this->createShippingDiscounts($order, $discountContext),
            $this->createLineItemDiscounts($order, $discountContext)
        );

        $manager = $this->getAppliedDiscountManager();

        foreach ($appliedDiscounts as $appliedDiscount) {
            $manager->persist($appliedDiscount);
        }

        if ($flush) {
            $manager->flush($appliedDiscounts);
        }

        return $appliedDiscounts;
    }

    /**
     * Remove applied discounts by order
     *
     * @param Order $order
     * @param bool $flush
     */
    public function removeAppliedDiscountByOrder(Order $order, $flush = false)
    {
        $appliedDiscounts = $this->getAppliedDiscountRepository()->findByOrder($order);

        foreach ($appliedDiscounts as $appliedDiscount) {
            $this->removeAppliendDiscount($appliedDiscount);
        }

        if ($flush) {
            $this->getAppliedDiscountManager()->flush($appliedDiscounts);
        }
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
            ->setType($discountType)
            ->setAmount($discountInfo->getDiscountAmount())
            ->setCurrency($order->getCurrency())
            ->setConfigOptions($discountConfigurationOptions)
            ->setPromotion($promotion)
            ->setPromotionName($promotion->getRule()->getName())
            ->setSourcePromotionId($promotion->getId())
            ->setPromotionData($this->promotionNormalizer->normalize($promotion))
            ->setOrder($order);
    }

    /**
     * @return PromotionExecutor
     */
    protected function getPromotionExecutor(): PromotionExecutor
    {
        // Using DI container instead of concrete service due to circular reference
        return $this->container->get('oro_promotion.promotion_executor');
    }

    /**
     * @return EntityManager
     */
    protected function getAppliedDiscountManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass(AppliedDiscount::class);
    }

    /**
     * @return AppliedDiscountRepository|EntityRepository
     */
    protected function getAppliedDiscountRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(AppliedDiscount::class);
    }

    /**
     * @param AppliedDiscount $appliedDiscount
     * @param bool $flush
     * @return bool
     */
    protected function removeAppliendDiscount(AppliedDiscount $appliedDiscount, $flush = false)
    {
        $em = $this->getAppliedDiscountManager();

        if (!$em->contains($appliedDiscount)) {
            return false;
        }

        $em->remove($appliedDiscount);

        if ($flush) {
            $em->flush($appliedDiscount);
        }

        return true;
    }

    /**
     * @param Order $order
     * @param DiscountContext $discountContext
     * @return AppliedDiscount[]
     */
    protected function createSubtotalDiscounts(Order $order, DiscountContext $discountContext)
    {
        $subtotalDiscounts = [];

        foreach ($discountContext->getSubtotalDiscountsInformation() as $subtotalDiscountInfo) {
            $subtotalDiscounts[] = $this->createAppliedDiscount($order, $subtotalDiscountInfo);
        }

        return $subtotalDiscounts;
    }

    /**
     * @param Order $order
     * @param DiscountContext $discountContext
     * @return AppliedDiscount[]
     */
    protected function createShippingDiscounts(Order $order, DiscountContext $discountContext)
    {
        $shippingDiscounts = [];

        foreach ($discountContext->getShippingDiscountsInformation() as $shippingDiscountInfo) {
            $shippingDiscount = $this->createAppliedDiscount($order, $shippingDiscountInfo);
            $shippingDiscounts[] = $shippingDiscount;
        }

        return $shippingDiscounts;
    }

    /**
     * @param Order $order
     * @param DiscountContext $discountContext
     * @return AppliedDiscount[]
     */
    protected function createLineItemDiscounts(Order $order, DiscountContext $discountContext)
    {
        $lineItemDiscounts = [];

        foreach ($discountContext->getLineItems() as $discountLineItem) {
            foreach ($discountLineItem->getDiscountsInformation() as $discountInfo) {
                $appliedDiscount = $this->createAppliedDiscount($order, $discountInfo);

                $lineItem = $discountLineItem->getSourceLineItem();
                if ($lineItem instanceof OrderLineItem) {
                    $appliedDiscount->setLineItem($lineItem);
                }

                $lineItemDiscounts[] = $appliedDiscount;
            }
        }

        return $lineItemDiscounts;
    }
}
