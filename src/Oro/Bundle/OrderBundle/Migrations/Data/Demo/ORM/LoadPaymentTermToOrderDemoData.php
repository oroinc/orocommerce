<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentRuleIntegrationData;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads PaymentTerm demo data for exist orders
 */
class LoadPaymentTermToOrderDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadPaymentRuleIntegrationData::class,
            LoadPaymentTermDemoData::class,
            LoadOrderLineItemDemoData::class,
            LoadCustomerOrderLineItemsDemoData::class,
        ];
    }

    /**
     * @param EntityManager $manager
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

        $manager->flush();
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
