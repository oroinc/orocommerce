<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItems;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;

class LoadAppliedPromotionData extends AbstractFixture implements DependentFixtureInterface
{
    const SIMPLE_APPLIED_DISCOUNT = 'simple_applied_discount';
    const SIMPLE_APPLIED_DISCOUNT_WITH_LINE_ITEM = 'simple_applied_discount_with_line_item';

    /** @var array */
    protected static $appliedDiscounts = [
        self::SIMPLE_APPLIED_DISCOUNT => [
            'order' => LoadOrders::ORDER_1,
            'type' => 'order',
            'amount' => 10.00,
            'currency' => 'USD',
            'promotion_name' => 'Some name',
            'source_promotion_id' => 0
        ],
        self::SIMPLE_APPLIED_DISCOUNT_WITH_LINE_ITEM => [
            'order' => LoadOrders::ORDER_1,
            'type' => 'lineItem',
            'amount' => 10.00,
            'currency' => 'USD',
            'promotion_name' => 'Some line item discount name',
            'lineItem' => LoadOrderLineItems::ITEM_1,
            'source_promotion_id' => 0
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrders::class,
            LoadOrderLineItems::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach (static::$appliedDiscounts as $reference => $appliedDiscountData) {
            /** @var Order $order */
            $order = $this->getReference($appliedDiscountData['order']);

            $appliedPromotion = (new AppliedPromotion())
                ->setType($appliedDiscountData['type'])
                ->setPromotionName($appliedDiscountData['promotion_name'])
                ->setSourcePromotionId($appliedDiscountData['source_promotion_id'])
                ->setOrder($order);

            $appliedDiscount = new AppliedDiscount();
            $appliedDiscount->setAmount($appliedDiscountData['amount']);
            $appliedDiscount->setCurrency($appliedDiscountData['currency']);

            $appliedPromotion->addAppliedDiscount($appliedDiscount);

            if (isset($appliedDiscountData['lineItem'])) {
                /** @var OrderLineItem $orderLineItem */
                $orderLineItem = $this->getReference($appliedDiscountData['lineItem']);
                $appliedDiscount->setLineItem($orderLineItem);
            }

            $manager->persist($appliedPromotion);

            $this->setReference($reference, $appliedDiscount);
        }
        $manager->flush();
    }
}
