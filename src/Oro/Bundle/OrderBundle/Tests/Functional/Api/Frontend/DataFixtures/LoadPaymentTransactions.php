<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures\LoadMoneyOrderChannelData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Loads payment transactions for test orders.
 */
class LoadPaymentTransactions extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml',
            LoadMoneyOrderChannelData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $channel = $this->getReference('money_order:channel_1');

        $transaction1 = new PaymentTransaction();
        $transaction1->setEntityClass(Order::class);
        $transaction1->setEntityIdentifier($this->getReference('order1')->getId());
        $transaction1->setPaymentMethod('money_order_' . $channel->getId());
        $transaction1->setAction('purchase');
        $transaction1->setAmount('10');
        $transaction1->setCurrency('USD');
        $manager->persist($transaction1);

        $transaction2 = new PaymentTransaction();
        $transaction2->setEntityClass(Order::class);
        $transaction2->setEntityIdentifier($this->getReference('order2')->getId());
        $transaction2->setPaymentMethod('money_order_' . $channel->getId());
        $transaction2->setAction('purchase');
        $transaction2->setAmount('20');
        $transaction2->setCurrency('USD');
        $manager->persist($transaction2);

        $transaction3 = new PaymentTransaction();
        $transaction3->setEntityClass(Order::class);
        $transaction3->setEntityIdentifier($this->getReference('order3')->getId());
        $transaction3->setPaymentMethod('money_order_' . $channel->getId());
        $transaction3->setAction('pending');
        $transaction3->setAmount('30');
        $transaction3->setCurrency('USD');
        $manager->persist($transaction3);

        $manager->flush();

        $this->setReference('money_order_channel_1', $channel);
    }
}
