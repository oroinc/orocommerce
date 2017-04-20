<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Brick\Math\BigDecimal;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;

class LoadOrderDiscounts extends AbstractFixture implements DependentFixtureInterface
{
    const REFERENCE_DISCOUNT_PERCENT = 'orderDiscount.percent';
    const REFERENCE_DISCOUNT_AMOUNT = 'orderDiscount.amount';

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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getDiscounts() as $reference => $discount) {
            $manager->persist($discount);
            $this->addReference($reference, $discount);
        }

        $manager->flush();
    }

    /**
     * @return OrderDiscount[]
     */
    private function getDiscounts()
    {
        $order = $this->getOrder();

        $percentDiscount = 20.1;
        $amountDiscount = round($order->getSubtotal() * $percentDiscount / 100, 4);

        $discount1 = new OrderDiscount();
        $discount1->setDescription('Discount 1')
            ->setPercent(BigDecimal::of($percentDiscount)->toFloat())
            ->setAmount(BigDecimal::of($amountDiscount)->toFloat())
            ->setOrder($order)
            ->setType(OrderDiscount::TYPE_PERCENT);

        $discount2 = new OrderDiscount();
        $discount2->setDescription('Discount 2')
            ->setPercent(BigDecimal::of($percentDiscount)->toFloat())
            ->setAmount(BigDecimal::of($amountDiscount)->toFloat())
            ->setOrder($order)
            ->setType(OrderDiscount::TYPE_AMOUNT);

        return [
            self::REFERENCE_DISCOUNT_PERCENT => $discount1,
            self::REFERENCE_DISCOUNT_AMOUNT => $discount2,
        ];
    }

    /**
     * @return Order
     */
    private function getOrder()
    {
        return $this->getReference(LoadOrders::ORDER_1);
    }
}
