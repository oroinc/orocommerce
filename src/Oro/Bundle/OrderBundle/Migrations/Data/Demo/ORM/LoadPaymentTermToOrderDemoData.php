<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentRuleIntegrationData;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPaymentTermToOrderDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadPaymentRuleIntegrationData::class,
            LoadPaymentTermDemoData::class,
            LoadOrderDemoData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $paymentTransactionProvider = $this->container->get('oro_payment.provider.payment_transaction');

        $orders = $this->container->get('doctrine')->getRepository('OroOrderBundle:Order')->findAll();

        /** @var Order[] $orders */
        foreach ($orders as $order) {
            $paymentTransaction = $paymentTransactionProvider->getPaymentTransaction($order);
            if (!$paymentTransaction) {
                $paymentTransaction = $paymentTransactionProvider->createPaymentTransaction(
                    $this->getPaymentTermMethodIdentifier(),
                    PaymentMethodInterface::PURCHASE,
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

    /**
     * @return string
     */
    private function getPaymentTermMethodIdentifier()
    {
        return $this->container->get('oro_payment_term.config.integration_method_identifier_generator')
            ->generateIdentifier($this->getPaymentTermIntegrationChannel());
    }

    /**
     * @return Channel|object
     */
    private function getPaymentTermIntegrationChannel()
    {
        return $this->getReference(LoadPaymentRuleIntegrationData::PAYMENT_TERM_INTEGRATION_CHANNEL_REFERENCE);
    }
}
