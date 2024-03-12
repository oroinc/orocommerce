<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
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
    public function getDependencies(): array
    {
        return [
            LoadPaymentRuleIntegrationData::class,
            LoadPaymentTermDemoData::class,
            LoadOrderLineItemDemoData::class,
            LoadCustomerOrderLineItemsDemoData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $paymentTransactionProvider = $this->container->get('oro_payment.provider.payment_transaction');

        /** @var Order[] $orders */
        $orders = $manager->getRepository(Order::class)->findAll();
        foreach ($orders as $order) {
            $paymentTransaction = $paymentTransactionProvider->getPaymentTransaction($order);
            if (!$paymentTransaction) {
                $paymentTransaction = $paymentTransactionProvider->createPaymentTransaction(
                    $this->getPaymentTermMethodIdentifier(),
                    PaymentMethodInterface::PURCHASE,
                    $order
                );
            }

            $paymentTransaction->setAmount($order->getTotal());
            $paymentTransaction->setCurrency($order->getCurrency());
            $paymentTransaction->setSuccessful(true);

            $paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
        }

        $manager->flush();
    }

    private function getPaymentTermMethodIdentifier(): string
    {
        return $this->container->get('oro_payment_term.config.integration_method_identifier_generator')
            ->generateIdentifier($this->getPaymentTermIntegrationChannel());
    }

    private function getPaymentTermIntegrationChannel(): Channel
    {
        return $this->getReference(LoadPaymentRuleIntegrationData::PAYMENT_TERM_INTEGRATION_CHANNEL_REFERENCE);
    }
}
