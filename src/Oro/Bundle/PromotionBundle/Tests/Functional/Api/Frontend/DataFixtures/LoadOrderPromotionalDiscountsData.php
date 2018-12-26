<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Api\Frontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\TaxBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadOrderTaxesData;

class LoadOrderPromotionalDiscountsData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentData::class,
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml',
            LoadOrderTaxesData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $rule = new Rule();
        $rule->setName('test rule');
        $rule->setSortOrder(1);
        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('amount');
        $promotion = new Promotion();
        $promotion->setOwner($this->getReference('user'));
        $promotion->setRule($rule);
        $promotion->setDiscountConfiguration($discountConfiguration);
        $promotion->setProductsSegment($this->getReference(LoadSegmentData::PRODUCT_DYNAMIC_EMPTY_SEGMENT));
        $manager->persist($rule);
        $manager->persist($discountConfiguration);
        $manager->persist($promotion);
        $manager->flush();

        /** @var Order $order1 */
        $order1 = $this->getReference('order1');
        $this->assertOrderAppliedDiscountNotExists($manager, $order1);
        $order1Discount1 = $this->createOrderAppliedDiscount($order1, 'order', 1.2, $promotion->getId());
        $manager->persist($order1Discount1->getAppliedPromotion());
        $manager->persist($order1Discount1);
        $this->setReference('order1_discount', $order1Discount1);
        $order1Discount2 = $this->createOrderAppliedDiscount($order1, 'shipping', 0.3, $promotion->getId());
        $manager->persist($order1Discount2->getAppliedPromotion());
        $manager->persist($order1Discount2);
        $this->setReference('order1_discount_shipping', $order1Discount2);

        /** @var OrderLineItem $order1LineItem1 */
        $order1LineItem1 = $this->getReference('order1_line_item1');
        $this->assertLineItemAppliedDiscountNotExists($manager, $order1LineItem1);
        $order1LineItem1Discount1 = $this->createLineItemAppliedDiscount($order1LineItem1, 3.1, $promotion->getId());
        $manager->persist($order1LineItem1Discount1->getAppliedPromotion());
        $manager->persist($order1LineItem1Discount1);
        $this->setReference('order1_line_item1_discount1', $order1LineItem1Discount1);
        $order1LineItem1Discount2 = $this->createLineItemAppliedDiscount($order1LineItem1, 1.0, $promotion->getId());
        $manager->persist($order1LineItem1Discount2->getAppliedPromotion());
        $manager->persist($order1LineItem1Discount2);
        $this->setReference('order1_line_item1_discount2', $order1LineItem1Discount2);

        /** @var Order $order2 */
        $order2 = $this->getReference('order2');
        $this->assertOrderAppliedDiscountNotExists($manager, $order2);

        /** @var OrderLineItem $order2LineItem1 */
        $order2LineItem1 = $this->getReference('order2_line_item1');
        $this->assertLineItemAppliedDiscountNotExists($manager, $order2LineItem1);
        $order2LineItem1Discount = $this->createLineItemAppliedDiscount($order2LineItem1, 1.0, $promotion->getId());
        $manager->persist($order2LineItem1Discount->getAppliedPromotion());
        $manager->persist($order2LineItem1Discount);
        $this->setReference('order2_line_item1_discount1', $order2LineItem1Discount);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param Order         $order
     */
    private function assertOrderAppliedDiscountNotExists(ObjectManager $manager, Order $order)
    {
        /** @var EntityRepository $repo */
        $repo = $manager->getRepository(AppliedDiscount::class);
        $appliedDiscount = $repo->createQueryBuilder('discounts')
            ->innerJoin('discounts.appliedPromotion', 'promotions')
            ->where('promotions.order = :order AND discounts.lineItem IS NULL')
            ->setParameter('order', $order)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if (null !== $appliedDiscount) {
            throw new \LogicException(sprintf(
                'The applied discount must not exist for %s (ID: %s)',
                Order::class,
                $order->getId()
            ));
        }
    }

    /**
     * @param ObjectManager $manager
     * @param OrderLineItem $lineItem
     */
    private function assertLineItemAppliedDiscountNotExists(ObjectManager $manager, OrderLineItem $lineItem)
    {
        /** @var EntityRepository $repo */
        $repo = $manager->getRepository(AppliedDiscount::class);
        $appliedDiscount = $repo->findOneBy(['lineItem' => $lineItem]);
        if (null !== $appliedDiscount) {
            throw new \LogicException(sprintf(
                'The applied discount must not exist for %s (ID: %s)',
                OrderLineItem::class,
                $lineItem->getId()
            ));
        }
    }

    /**
     * @param Order  $order
     * @param string $type
     * @param float  $amount
     * @param int    $promotionId
     *
     * @return AppliedDiscount
     */
    private function createOrderAppliedDiscount(
        Order $order,
        string $type,
        float $amount,
        int $promotionId
    ): AppliedDiscount {
        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setType($type);
        $appliedPromotion->setSourcePromotionId($promotionId);
        $appliedPromotion->setPromotionName('test promotion');
        $appliedPromotion->setOrder($order);
        $appliedDiscount = new AppliedDiscount();
        $appliedDiscount->setAppliedPromotion($appliedPromotion);
        $appliedDiscount->setAmount($amount);
        $appliedDiscount->setCurrency($order->getCurrency());

        return $appliedDiscount;
    }

    /**
     * @param OrderLineItem $lineItem
     * @param float         $amount
     * @param int           $promotionId
     *
     * @return AppliedDiscount
     */
    private function createLineItemAppliedDiscount(
        OrderLineItem $lineItem,
        float $amount,
        int $promotionId
    ): AppliedDiscount {
        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setType('line_item');
        $appliedPromotion->setSourcePromotionId($promotionId);
        $appliedPromotion->setPromotionName('test promotion');
        $appliedPromotion->setOrder($lineItem->getOrder());
        $appliedDiscount = new AppliedDiscount();
        $appliedDiscount->setLineItem($lineItem);
        $appliedDiscount->setAppliedPromotion($appliedPromotion);
        $appliedDiscount->setAmount($amount);
        $appliedDiscount->setCurrency($lineItem->getCurrency());

        return $appliedDiscount;
    }
}
