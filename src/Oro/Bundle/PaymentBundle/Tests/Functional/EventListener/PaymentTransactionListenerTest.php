<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Functional\EventListener;

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
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
final class PaymentTransactionListenerTest extends WebTestCase
{
    private ManagerRegistry $managerRegistry;
    private PaymentStatusManager $paymentStatusManager;
    private PaymentTransactionProvider $paymentTransactionProvider;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->managerRegistry = self::getContainer()->get('doctrine');
        $this->paymentStatusManager = self::getContainer()->get('oro_payment.manager.payment_status');
        $this->paymentTransactionProvider = self::getContainer()->get('oro_payment.provider.payment_transaction');

        $this->loadFixtures([LoadCustomerUserData::class]);
    }

    public function testPendingWhenNoTransactions(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-PENDING');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Get payment status without any transactions
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);

        self::assertNotNull($paymentStatus);
        self::assertEquals(PaymentStatuses::PENDING, $paymentStatus->getPaymentStatus());
        self::assertFalse($paymentStatus->isForced());

        // Verify no payment transactions exist for this order
        $transactions = $this->paymentTransactionProvider->getPaymentTransactions($order);
        self::assertEmpty($transactions);
    }

    public function testRefundedPartiallyWhenCapture(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-REFUND-PARTIAL-CAPTURE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create initial capture transaction
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        // Create partial refund transaction
        $refundTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            30.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction);

        // Verify refunded partially status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::REFUNDED_PARTIALLY, $paymentStatus->getPaymentStatus());
    }

    public function testRefundedPartiallyWhenCharge(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-REFUND-PARTIAL-CHARGE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create initial charge transaction
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CHARGE,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        // Create partial refund transaction
        $refundTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            30.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction);

        // Verify refunded partially status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::REFUNDED_PARTIALLY, $paymentStatus->getPaymentStatus());
    }

    public function testRefundedPartiallyWhenPurchase(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-REFUND-PARTIAL-PURCHASE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create initial purchase transaction
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::PURCHASE,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        // Create partial refund transaction
        $refundTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            30.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction);

        // Verify refunded partially status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::REFUNDED_PARTIALLY, $paymentStatus->getPaymentStatus());
    }

    public function testRefundedPartiallyWhenMultipleTransactions(): void
    {
        $order = $this->prepareOrderObject(150.0, '#TEST-REFUND-MULTIPLE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create three transactions
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            80.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        $chargeTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CHARGE,
            60.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($chargeTransaction);

        $purchaseTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::PURCHASE,
            10.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($purchaseTransaction);

        // Create first partial refund
        $refundTransaction1 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            25.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction1);

        // Create second partial refund
        $refundTransaction2 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            35.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction2);

        // Should be refunded partially (total refunded: 60, total paid: 150)
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::REFUNDED_PARTIALLY, $paymentStatus->getPaymentStatus());
    }

    public function testRefundedWhenCapture(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-REFUND-FULL-CAPTURE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create initial capture transaction
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        // Create full refund transaction
        $refundTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction);

        // Verify fully refunded status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::REFUNDED, $paymentStatus->getPaymentStatus());
    }

    public function testRefundedWhenCharge(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-REFUND-FULL-CHARGE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create initial charge transaction
        $chargeTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CHARGE,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($chargeTransaction);

        // Create full refund transaction
        $refundTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction);

        // Verify fully refunded status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::REFUNDED, $paymentStatus->getPaymentStatus());
    }

    public function testRefundedWhenPurchase(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-REFUND-FULL-PURCHASE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create initial purchase transaction
        $purchaseTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::PURCHASE,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($purchaseTransaction);

        // Create full refund transaction
        $refundTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction);

        // Verify fully refunded status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::REFUNDED, $paymentStatus->getPaymentStatus());
    }

    public function testRefundedWithMultipleTransactions(): void
    {
        $order = $this->prepareOrderObject(200.0, '#TEST-REFUND-MULTIPLE-FULL');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create multiple transactions
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            80.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        $chargeTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CHARGE,
            120.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($chargeTransaction);

        $purchaseTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::PURCHASE,
            10.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($purchaseTransaction);

        // Create multiple refund transactions that equal the total paid amount
        $refundTransaction1 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            80.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction1);

        $refundTransaction2 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            70.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction2);

        $refundTransaction3 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::REFUND,
            50.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($refundTransaction3);

        // Should now be fully refunded (total refunded: 200, total paid: 200)
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::REFUNDED, $paymentStatus->getPaymentStatus());
    }

    public function testPaidInFullWhenCapture(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-PAID-FULL-CAPTURE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create capture transaction for full amount
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        // Verify fully paid status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $paymentStatus->getPaymentStatus());
    }

    public function testPaidInFullWhenCharge(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-PAID-FULL-CHARGE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create charge transaction for full amount
        $chargeTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CHARGE,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($chargeTransaction);

        // Verify fully paid status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $paymentStatus->getPaymentStatus());
    }

    public function testPaidInFullWhenPurchase(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-PAID-FULL-PURCHASE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create purchase transaction for full amount
        $purchaseTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::PURCHASE,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($purchaseTransaction);

        // Verify fully paid status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $paymentStatus->getPaymentStatus());
    }

    public function testPaidInFullWithMultipleTransactions(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-PAID-MULTIPLE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create multiple capture transactions that sum to full amount
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            40.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        $chargeTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CHARGE,
            50.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($chargeTransaction);

        $purchaseTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::PURCHASE,
            10.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($purchaseTransaction);

        // Verify fully paid status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $paymentStatus->getPaymentStatus());
    }

    public function testInvoiced(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-INVOICED');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create invoice transaction
        $invoiceTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::INVOICE,
            100.0,
            true,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($invoiceTransaction);

        // Verify invoiced status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::INVOICED, $paymentStatus->getPaymentStatus());
    }

    public function testInvoicedWithMultipleTransactions(): void
    {
        $order = $this->prepareOrderObject(150.0, '#TEST-INVOICED-MULTIPLE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create multiple transactions including invoice
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            50.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        $invoiceTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::INVOICE,
            150.0,
            true,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($invoiceTransaction);

        // Verify invoiced status (invoice takes precedence)
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::INVOICED, $paymentStatus->getPaymentStatus());
    }

    public function testCanceledPartially(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-CANCELED-PARTIAL');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create authorize transaction
        $authorizeTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE,
            100.0,
            true,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($authorizeTransaction);

        // Create partial cancel transaction
        $cancelTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CANCEL,
            30.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($cancelTransaction);

        // Verify canceled partially status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::CANCELED_PARTIALLY, $paymentStatus->getPaymentStatus());
    }

    public function testCanceledPartiallyWithMultipleTransactions(): void
    {
        $order = $this->prepareOrderObject(200.0, '#TEST-CANCELED-PARTIAL-MULTIPLE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create multiple authorize transactions
        $authorizeTransaction1 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE,
            100.0,
            true,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($authorizeTransaction1);

        $authorizeTransaction2 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE,
            100.0,
            true,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($authorizeTransaction2);

        // Create partial cancel transactions
        $cancelTransaction1 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CANCEL,
            50.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($cancelTransaction1);

        $cancelTransaction2 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CANCEL,
            30.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($cancelTransaction2);

        // Verify canceled partially status (canceled: 80, paid: 200)
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::CANCELED_PARTIALLY, $paymentStatus->getPaymentStatus());
    }

    public function testPaidPartiallyWhenCapture(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-PAID-PARTIAL-CAPTURE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create partial capture transaction
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            60.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        // Verify paid partially status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $paymentStatus->getPaymentStatus());
    }

    public function testPaidPartiallyWhenCharge(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-PAID-PARTIAL-CHARGE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create partial charge transaction
        $chargeTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CHARGE,
            60.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($chargeTransaction);

        // Verify paid partially status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $paymentStatus->getPaymentStatus());
    }

    public function testPaidPartiallyWhenPurchase(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-PAID-PARTIAL-PURCHASE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create partial purchase transaction
        $purchaseTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::PURCHASE,
            60.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($purchaseTransaction);

        // Verify paid partially status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $paymentStatus->getPaymentStatus());
    }

    public function testPaidPartiallyWithMultipleTransactions(): void
    {
        $order = $this->prepareOrderObject(150.0, '#TEST-PAID-PARTIAL-MULTIPLE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create multiple partial transactions
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            40.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        $chargeTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CHARGE,
            40.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($chargeTransaction);

        $purchaseTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::PURCHASE,
            10.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($purchaseTransaction);

        // Verify paid partially status (total paid: 90, order total: 150)
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::PAID_PARTIALLY, $paymentStatus->getPaymentStatus());
    }

    public function testAuthorized(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-AUTHORIZED');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create authorization transaction for full amount
        $authorizeTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE,
            100.0,
            true,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($authorizeTransaction);

        // Verify authorized status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::AUTHORIZED, $paymentStatus->getPaymentStatus());
    }

    public function testAuthorizedWithMultipleTransactions(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-AUTHORIZED-MULTIPLE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create multiple authorization transactions that sum to full amount
        $authorizeTransaction1 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE,
            60.0,
            true,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($authorizeTransaction1);

        $authorizeTransaction2 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE,
            40.0,
            true,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($authorizeTransaction2);

        // Verify authorized status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::AUTHORIZED, $paymentStatus->getPaymentStatus());
    }

    public function testCanceled(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-CANCELED');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create authorize transaction
        $captureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($captureTransaction);

        // Create full cancel transaction
        $cancelTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CANCEL,
            100.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($cancelTransaction);

        // Verify canceled status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);

        self::assertEquals(PaymentStatuses::CANCELED, $paymentStatus->getPaymentStatus());
    }

    public function testCanceledWithMultipleTransactions(): void
    {
        $order = $this->prepareOrderObject(150.0, '#TEST-CANCELED-MULTIPLE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create multiple authorize transactions
        $authorizeTransaction1 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE,
            80.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($authorizeTransaction1);

        $authorizeTransaction2 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE,
            70.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($authorizeTransaction2);

        // Create cancel transactions that equal or exceed the paid amount
        $cancelTransaction1 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CANCEL,
            80.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($cancelTransaction1);

        $cancelTransaction2 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CANCEL,
            70.0,
            false,
            true
        );

        $this->paymentTransactionProvider->savePaymentTransaction($cancelTransaction2);

        // Verify canceled status (canceled: 150, paid: 150)
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::CANCELED, $paymentStatus->getPaymentStatus());
    }

    public function testDeclinedWhenCapture(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-DECLINED-CAPTURE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create failed capture transaction
        $failedCaptureTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            100.0,
            false,
            false
        );

        $this->paymentTransactionProvider->savePaymentTransaction($failedCaptureTransaction);

        // Verify declined status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::DECLINED, $paymentStatus->getPaymentStatus());
    }

    public function testDeclinedWhenCharge(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-DECLINED-CHARGE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create failed charge transaction
        $failedChargeTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CHARGE,
            100.0,
            false,
            false
        );

        $this->paymentTransactionProvider->savePaymentTransaction($failedChargeTransaction);

        // Verify declined status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::DECLINED, $paymentStatus->getPaymentStatus());
    }

    public function testDeclinedWhenPurchase(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-DECLINED-PURCHASE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create failed purchase transaction
        $failedPurchaseTransaction = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::PURCHASE,
            100.0,
            false,
            false
        );

        $this->paymentTransactionProvider->savePaymentTransaction($failedPurchaseTransaction);

        // Verify declined status
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::DECLINED, $paymentStatus->getPaymentStatus());
    }

    public function testDeclinedWithMultipleTransactions(): void
    {
        $order = $this->prepareOrderObject(100.0, '#TEST-DECLINED-MULTIPLE');

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        // Create multiple failed transactions
        $failedTransaction1 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE,
            100.0,
            false,
            false
        );

        $this->paymentTransactionProvider->savePaymentTransaction($failedTransaction1);

        $failedTransaction2 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE,
            100.0,
            false,
            false
        );

        $this->paymentTransactionProvider->savePaymentTransaction($failedTransaction2);

        $failedTransaction3 = $this->createPaymentTransaction(
            $order,
            PaymentMethodInterface::CHARGE,
            100.0,
            false,
            false
        );

        $this->paymentTransactionProvider->savePaymentTransaction($failedTransaction3);

        // Verify declined status (all transactions failed)
        $em->clear();
        $paymentStatus = $this->paymentStatusManager->getPaymentStatus($order);
        self::assertEquals(PaymentStatuses::DECLINED, $paymentStatus->getPaymentStatus());
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

    private function createPaymentTransaction(
        Order $order,
        string $action,
        float $amount,
        bool $active,
        bool $successful
    ): PaymentTransaction {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setEntityClass(Order::class);
        $paymentTransaction->setEntityIdentifier($order->getId());
        $paymentTransaction->setAction($action);
        $paymentTransaction->setAmount((string)$amount);
        $paymentTransaction->setCurrency('USD');
        $paymentTransaction->setActive($active);
        $paymentTransaction->setSuccessful($successful);
        $paymentTransaction->setPaymentMethod('test_payment_method');

        // Don't persist here - let savePaymentTransaction handle it
        return $paymentTransaction;
    }

    private function getDefaultWebsite(): Website
    {
        return $this->managerRegistry->getRepository(Website::class)->findOneBy(['default' => true]);
    }
}
