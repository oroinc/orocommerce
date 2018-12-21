<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;

class LoadAppliedPromotionData extends AbstractFixture implements DependentFixtureInterface
{
    const SIMPLE_APPLIED_PROMOTION = 'simple_applied_promotion';
    const SHIPPING_APPLIED_PROMOTION = 'shipping_applied_promotion';
    const SIMPLE_APPLIED_PROMOTION_WITH_LINE_ITEM = 'simple_applied_promotion_with_line_item';

    /** @var array */
    protected static $appliedDiscounts = [
        self::SIMPLE_APPLIED_PROMOTION => [
            'coupon_code' => 'summer2000',
            'order' => LoadOrders::ORDER_1,
            'type' => 'order',
            'amount' => 10.00,
            'currency' => 'USD',
            'promotion_name' => 'Some name',
            'source_promotion_id' => 0
        ],
        self::SHIPPING_APPLIED_PROMOTION => [
            'order' => LoadOrders::ORDER_1,
            'type' => 'shipping',
            'amount' => 1.99,
            'currency' => 'USD',
            'promotion_name' => 'Some name',
            'source_promotion_id' => 0
        ],
        self::SIMPLE_APPLIED_PROMOTION_WITH_LINE_ITEM => [
            'order' => LoadOrders::ORDER_1,
            'type' => 'lineItem',
            'amount' => 10.00,
            'currency' => 'USD',
            'promotion_name' => 'Some line item discount name',
            'lineItem' => 'order_line_item.1',
            'source_promotion_id' => 0
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCouponData::class,
            LoadOrders::class,
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_line_items.yml'
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
                ->setSourcePromotionId($appliedDiscountData['source_promotion_id']);

            $appliedPromotion->setOrder($order);

            $this->addAppliedCoupon($appliedPromotion, $appliedDiscountData);

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

            $this->setReference($reference, $appliedPromotion);
        }
        $manager->flush();
    }

    /**
     * @param AppliedPromotion $appliedPromotion
     * @param array $data
     */
    private function addAppliedCoupon(AppliedPromotion $appliedPromotion, array $data)
    {
        if (!empty($data['coupon_code'])) {
            $appliedCoupon = (new AppliedCoupon())
                ->setCouponCode($data['coupon_code'])
                ->setSourcePromotionId(0)
                ->setSourceCouponId(0);

            $appliedPromotion->setAppliedCoupon($appliedCoupon);
        }
    }
}
