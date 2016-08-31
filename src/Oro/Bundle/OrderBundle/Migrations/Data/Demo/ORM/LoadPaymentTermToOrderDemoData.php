<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Method\PaymentTerm;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadPaymentTermToOrderDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM\LoadOrderDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $paymentTransactionProvider = $this->container->get('orob2b_payment.provider.payment_transaction');

        $orders = $this->container->get('doctrine')->getRepository('OroOrderBundle:Order')->findAll();

        /** @var Order[] $orders */
        foreach ($orders as $order) {
            $paymentTransaction = $paymentTransactionProvider->getPaymentTransaction($order);
            if (!$paymentTransaction) {
                $paymentTransaction = $paymentTransactionProvider->createPaymentTransaction(
                    PaymentTerm::TYPE,
                    PaymentTerm::PURCHASE,
                    $order
                );
            }

            $paymentTransaction
                ->setAmount($order->getTotal())
                ->setCurrency($order->getCurrency())
                ->setSuccessful(true);

            $paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
        }
    }
}
