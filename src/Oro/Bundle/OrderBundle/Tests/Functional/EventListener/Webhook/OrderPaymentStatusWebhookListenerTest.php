<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\EventListener\Webhook;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\IntegrationBundle\Async\Topic\SendWebhookNotificationTopic;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\Webhook\OrderPaymentStatusWebhookTopicListener;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderPaymentWebhookData;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Tests\Functional\WebsiteTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Verifies the full cycle: a completed payment transaction dispatches a TransactionCompleteEvent
 * that causes a webhook notification to be queued.
 *
 * @dbIsolationPerTest
 */
final class OrderPaymentStatusWebhookListenerTest extends WebTestCase
{
    use MessageQueueExtension;
    use WebsiteTrait;

    private ManagerRegistry $doctrine;
    private EventDispatcherInterface $eventDispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->doctrine = self::getContainer()->get('doctrine');
        $this->eventDispatcher = self::getContainer()->get('event_dispatcher');

        $this->loadFixtures([
            LoadCustomerUserData::class,
            LoadOrderPaymentWebhookData::class,
        ]);
    }

    public function testWebhookNotificationIsQueuedOnTransactionComplete(): void
    {
        $order = $this->createOrder(150.0, '#WH-001');

        $orderEm = $this->doctrine->getManagerForClass(Order::class);
        $orderEm->persist($order);
        $orderEm->flush();

        $transaction = $this->createTransaction($order, '100.25', PaymentMethodInterface::CAPTURE);

        $txEm = $this->doctrine->getManagerForClass(PaymentTransaction::class);
        $txEm->persist($transaction);
        $txEm->flush();

        $this->eventDispatcher->dispatch(
            new TransactionCompleteEvent($transaction),
            TransactionCompleteEvent::NAME
        );

        self::assertMessagesCount(SendWebhookNotificationTopic::NAME, 1);

        $message = self::getSentMessage(SendWebhookNotificationTopic::NAME);

        self::assertIsArray($message);
        self::assertSame(OrderPaymentStatusWebhookTopicListener::TOPIC, $message['topic']);
        self::assertNotEmpty($message['message_id']);

        $eventData = $message['event_data'];
        self::assertSame('orders', $eventData['data']['type']);
        self::assertSame($order->getId(), $eventData['data']['id']);

        $attributes = $eventData['data']['attributes'];
        self::assertSame('USD', $attributes['currency']);
        // The transaction (capture, 100.25, successful) is counted as amountPaid.
        self::assertSame(100.25, $attributes['amountPaid']);
        // amountDue = 150.0 (order total) - 100.25 (amountPaid) = 49.75
        self::assertSame(49.75, $attributes['amountDue']);
        // transactionAmount is a float cast of the raw transaction amount string.
        self::assertSame(100.25, $attributes['transactionAmount']);
        self::assertSame(PaymentMethodInterface::CAPTURE, $attributes['transactionType']);
        self::assertArrayHasKey('paymentStatus', $attributes);
        self::assertArrayHasKey('paymentStatusLabel', $attributes);
        self::assertArrayHasKey('transactionDate', $attributes);
    }

    public function testWebhookNotificationIsNotQueuedForAlreadyProcessedTransaction(): void
    {
        $order = $this->createOrder(150.0, '#WH-003');

        $orderEm = $this->doctrine->getManagerForClass(Order::class);
        $orderEm->persist($order);
        $orderEm->flush();

        $transaction = $this->createTransaction($order, '100.25', PaymentMethodInterface::CAPTURE);

        $txEm = $this->doctrine->getManagerForClass(PaymentTransaction::class);
        $txEm->persist($transaction);
        $txEm->flush();

        $event = new TransactionCompleteEvent($transaction);

        // First dispatch: notification must be queued and transaction marked as processed.
        $this->eventDispatcher->dispatch($event, TransactionCompleteEvent::NAME);

        // Second dispatch of the same transaction: must be skipped.
        $this->eventDispatcher->dispatch($event, TransactionCompleteEvent::NAME);

        self::assertMessagesCount(SendWebhookNotificationTopic::NAME, 1);
    }

    public function testWebhookNotificationIsNotQueuedWhenNoActiveWebhookSubscription(): void
    {
        $em = $this->doctrine->getManagerForClass(WebhookProducerSettings::class);
        $webhook = $this->getReference(LoadOrderPaymentWebhookData::WEBHOOK_PRODUCER_SETTINGS);
        $em->remove($webhook);
        $em->flush();

        $order = $this->createOrder(150.0, '#WH-002');

        $orderEm = $this->doctrine->getManagerForClass(Order::class);
        $orderEm->persist($order);
        $orderEm->flush();

        $transaction = $this->createTransaction($order, '100.25', PaymentMethodInterface::CAPTURE);

        $txEm = $this->doctrine->getManagerForClass(PaymentTransaction::class);
        $txEm->persist($transaction);
        $txEm->flush();

        $this->eventDispatcher->dispatch(
            new TransactionCompleteEvent($transaction),
            TransactionCompleteEvent::NAME
        );

        self::assertMessagesEmpty(SendWebhookNotificationTopic::NAME);
    }

    private function createOrder(float $total, string $poNumber): Order
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->doctrine->getRepository(CustomerUser::class)->findOneBy([]);

        $order = new Order();
        $order
            ->setOwner($user)
            ->setOrganization($user->getOrganization())
            ->setPoNumber($poNumber)
            ->setCurrency('USD')
            ->setSubtotal($total)
            ->setTotal($total)
            ->setCustomer($customerUser->getCustomer())
            ->setCustomerUser($customerUser)
            ->setWebsite($this->getDefaultWebsite());

        return $order;
    }

    private function createTransaction(Order $order, string $amount, string $action): PaymentTransaction
    {
        $transaction = new PaymentTransaction();
        $transaction
            ->setEntityClass(Order::class)
            ->setEntityIdentifier($order->getId())
            ->setPaymentMethod('test_payment')
            ->setAction($action)
            ->setAmount($amount)
            ->setCurrency('USD')
            ->setActive(false)
            ->setSuccessful(true)
            ->setCreatedAt(new \DateTime());

        return $transaction;
    }
}
