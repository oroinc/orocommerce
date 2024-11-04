<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
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

    private array $metadata = [];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadPaymentRuleIntegrationData::class,
            LoadPaymentTermDemoData::class,
            LoadOrderLineItemDemoData::class,
            LoadCustomerOrderLineItemsDemoData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->disableLifecycleCallbacks($manager);
        $this->toggleFeatures(false);

        $paymentTransactionProvider = $this->container->get('oro_payment.provider.payment_transaction');

        /** @var PaymentTransaction[] $paymentTransactions */
        $paymentTransactions = [];

        /** @var Order[] $orders */
        $orders = $manager->getRepository(Order::class)->findAll();
        foreach ($orders as $order) {
            /**
             * Skip payment transaction create and status update for orders w/o Total value
             */
            if ($order->getTotal() == 0) {
                continue;
            }

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

            /**
             * skip usage of PaymentTransactionProvider for status updates
             * as it is too time-consuming for bulk operations
             */
            //$paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

            $paymentTransactions[] = $paymentTransaction;
            $manager->persist($paymentTransaction);
        }

        $manager->flush();

        $doctrineHelper = $this->container->get('oro_entity.doctrine_helper');
        $paymentStatusProvider = $this->container->get('oro_payment.provider.payment_status');

        foreach ($paymentTransactions as $paymentTransaction) {
            $entityClass = $paymentTransaction->getEntityClass();
            $entityId = $paymentTransaction->getEntityIdentifier();
            $order = $doctrineHelper->getEntityReference($entityClass, $entityId);

            $paymentStatus = $paymentStatusProvider->getPaymentStatus($order);

            $paymentStatusEntity = new PaymentStatus();
            $paymentStatusEntity->setEntityClass($entityClass);
            $paymentStatusEntity->setEntityIdentifier($entityId);
            $paymentStatusEntity->setPaymentStatus($paymentStatus);

            $manager->persist($paymentStatusEntity);
        }

        $manager->flush();

        $this->enableLifecycleCallbacks($manager);
        $this->toggleFeatures(true);
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

    private function enableLifecycleCallbacks(ObjectManager $manager): void
    {
        $orderMetadata = $this->getClassMetadata($manager, Order::class);
        $lifecycleCallbacks = $orderMetadata->lifecycleCallbacks;
        array_unshift($lifecycleCallbacks['prePersist'], 'prePersist');
        $orderMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function disableLifecycleCallbacks(ObjectManager $manager): void
    {
        $orderMetadata = $this->getClassMetadata($manager, Order::class);
        $lifecycleCallbacks = $orderMetadata->lifecycleCallbacks;
        $lifecycleCallbacks['prePersist'] = array_diff($lifecycleCallbacks['prePersist'], ['prePersist']);
        $orderMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function getClassMetadata(ObjectManager $manager, string $className): ClassMetadata
    {
        if (!isset($this->metadata[$className])) {
            $this->metadata[$className] = $manager->getClassMetadata($className);
        }

        return $this->metadata[$className];
    }

    private function toggleFeatures(?bool $enable): void
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_promotion.feature_enabled', $enable ?? false);
        $configManager->flush();
    }
}
