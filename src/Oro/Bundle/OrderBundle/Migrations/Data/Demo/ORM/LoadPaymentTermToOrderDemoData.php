<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderChannelType;
use Oro\Bundle\MoneyOrderBundle\Migrations\Data\Demo\ORM\LoadCheckMoneyOrderIntegrationDemoData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PaymentTermBundle\Integration\PaymentTermChannelType;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentRuleIntegrationData;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads payment transaction demo data for orders
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
            LoadCheckMoneyOrderIntegrationDemoData::class,
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
        $channelRepository = $this->container->get('doctrine')->getRepository(Channel::class);

        /** @var IntegrationIdentifierGeneratorInterface $moneyOrderIdentifierGenerator */
        $moneyOrderIdentifierGenerator = $this->container
            ->get('oro_money_order.generator.money_order_config_identifier');
        $checkMoneyOrderMethodIdentifier = $moneyOrderIdentifierGenerator->generateIdentifier(
            $channelRepository->findOneBy(['type' => MoneyOrderChannelType::TYPE])
        );

        /** @var IntegrationIdentifierGeneratorInterface $paymentTermIdentifierGenerator */
        $paymentTermIdentifierGenerator = $this->container
            ->get('oro_payment_term.config.integration_method_identifier_generator');
        $paymentTermMethodIdentifier = $paymentTermIdentifierGenerator->generateIdentifier(
            $channelRepository->findOneBy(['type' => PaymentTermChannelType::TYPE])
        );

        $activeOrderStatuses = [
            OrderStatusesProviderInterface::INTERNAL_STATUS_PENDING,
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
        ];

        $ordersToUpdate = [];

        /** @var Order[] $orders */
        $orders = $manager->getRepository(Order::class)->findBy(['external' => false]);
        foreach ($orders as $order) {
            if ($order->getTotal() == 0) {
                // skip payment transaction create and status update for orders without Total value
                continue;
            }

            /** @var PaymentTransaction[] $newTransactions */
            $newTransactions = [];
            $paymentTransaction = $paymentTransactionProvider->getPaymentTransaction($order);
            if (!$paymentTransaction) {
                $ordersToUpdate[] = $order;

                $newTransactions[] = $paymentTransactionProvider->createPaymentTransaction(
                    $paymentTermMethodIdentifier,
                    PaymentMethodInterface::AUTHORIZE, /** @see PaymentTerm::purchase() */
                    $order
                );

                /**
                 * For 'open' and 'pending' orders we create only Payment Term "authorize" transaction.
                 * For other statuses we create both Payment Term "authorize" and Check/Money Order "purchase"
                 * transactions for the full amount, so that order payment status will be "paid in full".
                 *
                 * The reason for using the "Check/Money Order" method for "purchase" transactions is that the
                 * Payment Term method does not transfer any funds and would not normally create purchase transactions.
                 */
                $orderInternalStatusInternalId = $order->getInternalStatus()->getInternalId();
                if (!\in_array($orderInternalStatusInternalId, $activeOrderStatuses, true)) {
                    $newTransactions[] = $paymentTransactionProvider->createPaymentTransaction(
                        $checkMoneyOrderMethodIdentifier,
                        PaymentMethodInterface::CHARGE, /** @see MoneyOrder::capture() */
                        $order
                    );
                }
            }

            foreach ($newTransactions as $newTransaction) {
                $newTransaction->setAmount($order->getTotal());
                $newTransaction->setCurrency($order->getCurrency());
                $newTransaction->setSuccessful(true);

                // Do not use PaymentTransactionProvider for bulk updates as it is too time-consuming
                // $paymentTransactionProvider->savePaymentTransaction($newTransaction);

                $manager->persist($newTransaction);
            }
        }

        $manager->flush();

        /** @var PaymentStatusManager $paymentStatusManager */
        $paymentStatusManager = $this->container->get('oro_payment.manager.payment_status');

        foreach ($ordersToUpdate as $order) {
            $paymentStatusManager->updatePaymentStatus($order);
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
