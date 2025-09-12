<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentRuleIntegrationData;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads demo data for payment term of exist orders.
 */
class LoadPaymentTermToOrderDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadPaymentRuleIntegrationData::class,
            LoadPaymentTermDemoData::class,
            LoadOrderDemoData::class,
            LoadCustomerOrderDemoData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var EntityManagerInterface $manager */

        $this->toggleFeatures(false);

        /** @var PaymentTransactionProvider $paymentTransactionProvider */
        $paymentTransactionProvider = $this->container->get('oro_payment.provider.payment_transaction');
        /** @var IntegrationIdentifierGeneratorInterface $paymentTermIdentifierGenerator */
        $paymentTermIdentifierGenerator = $this->container->get(
            'oro_payment_term.config.integration_method_identifier_generator'
        );

        /** @var PaymentTransaction[] $paymentTransactions */
        $paymentTransactions = [];

        /** @var Order[] $orders */
        $orders = $manager->getRepository(Order::class)->findBy(['external' => false]);
        foreach ($orders as $order) {
            if (!$order->getTotal()) {
                // skip payment transaction create and status update for orders without Total value
                continue;
            }

            $paymentTransaction = $paymentTransactionProvider->getPaymentTransaction($order);
            if (!$paymentTransaction) {
                $paymentTransaction = $paymentTransactionProvider->createPaymentTransaction(
                    $paymentTermIdentifierGenerator->generateIdentifier(
                        $this->getReference(LoadPaymentRuleIntegrationData::PAYMENT_TERM_INTEGRATION_CHANNEL_REFERENCE)
                    ),
                    PaymentMethodInterface::PURCHASE,
                    $order
                );
            }
            $paymentTransaction->setAmount($order->getTotal());
            $paymentTransaction->setCurrency($order->getCurrency());
            $paymentTransaction->setSuccessful(true);

            $manager->persist($paymentTransaction);

            $paymentTransactions[] = $paymentTransaction;
        }

        $manager->flush();

        /** @var PaymentStatusManager $paymentStatusManager */
        $paymentStatusManager = $this->container->get('oro_payment.manager.payment_status');

        foreach ($paymentTransactions as $paymentTransaction) {
            $entityClass = $paymentTransaction->getEntityClass();
            $entityId = $paymentTransaction->getEntityIdentifier();
            /** @var Order $order */
            $order = $manager->getReference($entityClass, $entityId);

            $paymentStatus = $paymentStatusManager->updatePaymentStatus($order);
            $manager->persist($paymentStatus);
        }

        $manager->flush();

        $this->toggleFeatures(true);
    }

    private function toggleFeatures(?bool $enable): void
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_promotion.feature_enabled', $enable ?? false);
        $configManager->flush();
    }
}
