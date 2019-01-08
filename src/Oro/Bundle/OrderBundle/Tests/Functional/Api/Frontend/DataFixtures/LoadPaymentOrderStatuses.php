<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

/**
 * Loads calculated payment statuses for test orders.
 */
class LoadPaymentOrderStatuses extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $status1 = new PaymentStatus();
        $status1->setEntityClass(Order::class);
        $status1->setEntityIdentifier($this->getReference('order1')->getId());
        $status1->setPaymentStatus(PaymentStatusProvider::FULL);
        $manager->persist($status1);

        $status2 = new PaymentStatus();
        $status2->setEntityClass(Order::class);
        $status2->setEntityIdentifier($this->getReference('order2')->getId());
        $status2->setPaymentStatus(PaymentStatusProvider::PARTIALLY);
        $manager->persist($status2);

        $status3 = new PaymentStatus();
        $status3->setEntityClass(Order::class);
        $status3->setEntityIdentifier($this->getReference('order3')->getId());
        $status3->setPaymentStatus(PaymentStatusProvider::PENDING);
        $manager->persist($status3);

        $manager->flush();
    }
}
