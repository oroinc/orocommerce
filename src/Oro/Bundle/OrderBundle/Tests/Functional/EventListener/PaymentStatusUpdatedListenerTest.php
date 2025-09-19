<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Tests\Functional\WebsiteTrait;

final class PaymentStatusUpdatedListenerTest extends WebTestCase
{
    use WebsiteTrait;

    private ManagerRegistry $managerRegistry;
    private PaymentStatusManager $paymentStatusManager;
    private PaymentTransactionProvider $paymentTransactionProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->managerRegistry = self::getContainer()->get('doctrine');
        $this->paymentStatusManager = self::getContainer()->get('oro_payment.manager.payment_status');
        $this->paymentTransactionProvider = self::getContainer()->get('oro_payment.provider.payment_transaction');

        $this->loadFixtures([LoadCustomerUserData::class]);
    }

    public function testPaymentStatusWhenSingleOrder(): void
    {
        $order = $this->prepareOrderObject(100, '#41');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Test initial state - no payment transactions
        self::assertEquals(
            PaymentStatuses::PENDING,
            (string)$this->paymentStatusManager->getPaymentStatus($order)
        );

        // Add successful authorize transaction
        $this->addPaymentTransaction($order, PaymentMethodInterface::AUTHORIZE, true);

        self::assertEquals(
            PaymentStatuses::AUTHORIZED,
            (string)$this->paymentStatusManager->getPaymentStatus($order)
        );

        // Add successful capture transaction
        $this->addPaymentTransaction($order, PaymentMethodInterface::CAPTURE, true);

        self::assertEquals(
            PaymentStatuses::PAID_IN_FULL,
            (string)$this->paymentStatusManager->getPaymentStatus($order)
        );

        // Add partial refund transaction
        $refundTransaction = $this->addPaymentTransaction($order, PaymentMethodInterface::REFUND, true, 30.0);

        $em = $this->managerRegistry->getManagerForClass(PaymentTransaction::class);
        $em->persist($refundTransaction);
        $em->flush();

        self::assertEquals(
            PaymentStatuses::REFUNDED_PARTIALLY,
            (string)$this->paymentStatusManager->getPaymentStatus($order)
        );
    }

    public function testPaymentStatusWhenOrderWithSubOrders(): void
    {
        $subOrder1 = $this->prepareOrderObject(50, '#42-1');
        $subOrder2 = $this->prepareOrderObject(50, '#42-2');

        $order = $this->prepareOrderObject(100, '#42');
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        $this->addPaymentTransaction($subOrder1, PaymentMethodInterface::AUTHORIZE, true);
        $this->addPaymentTransaction($subOrder2, PaymentMethodInterface::AUTHORIZE, false);

        self::assertEquals(
            PaymentStatuses::AUTHORIZED,
            (string)$this->paymentStatusManager->getPaymentStatus($subOrder1)
        );

        self::assertEquals(
            PaymentStatuses::PENDING,
            (string)$this->paymentStatusManager->getPaymentStatus($subOrder2)
        );

        self::assertEquals(
            PaymentStatuses::AUTHORIZED_PARTIALLY,
            (string)$this->paymentStatusManager->getPaymentStatus($order)
        );
    }

    private function prepareOrderObject(float $total, string $poNumber): Order
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        if (!$user->getOrganization()) {
            $user->setOrganization($this->managerRegistry->getRepository(Organization::class)->findOneBy([]));
        }
        /** @var CustomerUser $customerUser */
        $customerUser = $this->managerRegistry->getRepository(CustomerUser::class)->findOneBy([]);

        $order = new Order();
        $order
            ->setOwner($user)
            ->setPoNumber($poNumber)
            ->setOrganization($user->getOrganization())
            ->setCurrency('USD')
            ->setSubtotal($total)
            ->setTotal($total)
            ->setCustomer($customerUser->getCustomer())
            ->setWebsite($this->getDefaultWebsite())
            ->setCustomerUser($customerUser);

        return $order;
    }

    private function addPaymentTransaction(
        Order $order,
        string $transactionType,
        bool $transactionStatus,
        ?float $amount = null
    ): PaymentTransaction {
        $transaction = $this->paymentTransactionProvider->createPaymentTransaction('pm1', $transactionType, $order);
        $transaction->setSuccessful($transactionStatus);
        $transaction->setActive(true);
        $transaction->setAmount($amount ?? $order->getTotal());
        $transaction->setCurrency($order->getCurrency());

        $this->paymentTransactionProvider->savePaymentTransaction($transaction);

        return $transaction;
    }
}
