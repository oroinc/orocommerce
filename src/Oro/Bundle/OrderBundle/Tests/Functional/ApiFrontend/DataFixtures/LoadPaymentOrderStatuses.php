<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads calculated payment statuses for test orders.
 */
class LoadPaymentOrderStatuses extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies()
    {
        return [
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml',
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        /** @var PaymentStatusManager $paymentStatusManager */
        $paymentStatusManager = $this->container->get('oro_payment.manager.payment_status');

        $status1 = $paymentStatusManager->setPaymentStatus(
            $this->getReference('order1'),
            PaymentStatuses::PAID_IN_FULL
        );
        $manager->persist($status1);

        $status2 = $paymentStatusManager->setPaymentStatus(
            $this->getReference('order2'),
            PaymentStatuses::PAID_PARTIALLY
        );
        $manager->persist($status2);

        $status3 = $paymentStatusManager->setPaymentStatus(
            $this->getReference('order3'),
            PaymentStatuses::PENDING
        );
        $manager->persist($status3);

        $status4 = $paymentStatusManager->setPaymentStatus(
            $this->getReference('order4'),
            PaymentStatuses::PENDING
        );
        $manager->persist($status4);

        $status5 = $paymentStatusManager->setPaymentStatus(
            $this->getReference('order5'),
            PaymentStatuses::PENDING
        );
        $manager->persist($status5);

        $manager->flush();
    }
}
