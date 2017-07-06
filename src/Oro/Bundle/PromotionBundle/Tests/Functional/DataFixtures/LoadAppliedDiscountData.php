<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;

class LoadAppliedDiscountData extends AbstractFixture implements DependentFixtureInterface
{
    const SIMPLE_APPLIED_DISCOUNT = 'simple_applied_discount';

    /** @var array */
    protected static $appliedDiscounts = [
        self::SIMPLE_APPLIED_DISCOUNT => [
            'order' => LoadOrders::ORDER_1,
            'type' => 'order',
            'amount' => 10.00,
            'currency' => 'USD',
            'promotion_name' => 'Some name',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrders::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach (static::$appliedDiscounts as $reference => $appliedDiscountData) {
            $appliedDiscount = new AppliedDiscount();
            $appliedDiscount->setType($appliedDiscountData['type']);
            $appliedDiscount->setAmount($appliedDiscountData['amount']);
            $appliedDiscount->setCurrency($appliedDiscountData['currency']);
            $appliedDiscount->setPromotionName($appliedDiscountData['promotion_name']);

            /** @var Order $order */
            $order = $this->getReference($appliedDiscountData['order']);
            $appliedDiscount->setOrder($order);

            $manager->persist($appliedDiscount);

            $this->setReference($reference, $appliedDiscount);
        }
        $manager->flush();
    }
}
