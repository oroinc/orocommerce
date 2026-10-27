<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\EventListener\Callback\RedirectListener;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response as PayflowResponse;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;
use Oro\Bundle\PayPalBundle\Tests\Functional\Controller\Frontend\Stub\PaymentCallbackStubListener;
use Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData;
use Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPayPalChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class CallbackControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadPaymentTransactionData::class]);
    }

    /**
     * @dataProvider allowedIPsDataProvider
     */
    public function testNotifyChangeState(string $remoteAddress)
    {
        $parameters = [
            'PNREF' => 'REFERENCE',
            'RESULT' => '0',
            'SECURETOKEN' => 'SECURETOKEN',
            'SECURETOKENID' => 'SECURETOKENID',
        ];

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(LoadPaymentTransactionData::PAYFLOW_CHARGE_TRANSACTION);
        $this->assertTrue($paymentTransaction->isActive());
        $this->assertTrue($paymentTransaction->isSuccessful());

        $expectedData = $parameters + $paymentTransaction->getRequest();
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_payment_callback_notify',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ]
            ),
            $expectedData,
            [],
            ['REMOTE_ADDR' => $remoteAddress]
        );

        // Active flag of charge transaction after complete(notify request) action should be changed to false
        $this->assertFalse($paymentTransaction->isActive());
        $this->assertTrue($paymentTransaction->isSuccessful());
        $this->assertEquals($expectedData, $paymentTransaction->getResponse());
    }

    public function allowedIPsDataProvider(): array
    {
        return [
            'Paypal\'s IP address 1 should be allowed' => ['255.255.255.1'],
            'Paypal\'s IP address 2 should be allowed' => ['255.255.255.2'],
            'Paypal\'s IP address 3 should be allowed' => ['255.255.255.3'],
            'Paypal\'s IP address 4 should be allowed' => ['255.255.254.1'],
        ];
    }

    /**
     * @dataProvider allowedIPsDataProvider
     */
    public function testNotifyAllowedIPFiltering(string $remoteAddress)
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(LoadPaymentTransactionData::PAYFLOW_AUTHORIZE_TRANSACTION);

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_payment_callback_notify',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ]
            ),
            [],
            [],
            ['REMOTE_ADDR' => $remoteAddress]
        );

        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    public function notAllowedIPsDataProvider(): array
    {
        return [
            'Google\'s IP address 5 should not be allowed' => ['216.58.214.206'],
            'Facebook\'s IP address 6 should not be allowed' => ['173.252.120.68'],
        ];
    }

    /**
     * @dataProvider notAllowedIPsDataProvider
     */
    public function testNotifyNotAllowedIPFiltering(string $remoteAddress)
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(LoadPaymentTransactionData::PAYFLOW_AUTHORIZE_TRANSACTION);
        $this->assertTrue($paymentTransaction->isActive());

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_payment_callback_notify',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ]
            ),
            [],
            [],
            ['REMOTE_ADDR' => $remoteAddress]
        );

        // Check that original active flag was not changed
        $this->assertTrue($paymentTransaction->isActive());

        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    /**
     * RedirectListener::onError() stops the event propagation for a field format (invalid shipping address)
     * error, so CallbackHandler never gets to save the transaction itself (@see CallbackHandler::handle()).
     * The listener has to persist the failed transaction on its own - this proves it actually reaches the
     * database, not just the in-memory PaymentTransaction object the request happened to mutate.
     */
    public function testErrorCallbackPersistsFailedTransactionForFieldFormatError(): void
    {
        $paymentTransaction = $this->createFieldFormatErrorPaymentTransaction();

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_payment_callback_error',
                ['accessIdentifier' => $paymentTransaction->getAccessIdentifier()]
            )
        );

        $response = $this->client->getResponse();
        $this->assertNotNull($response);
        $this->assertResponseStatusCodeEquals($response, 302);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://example.com/failed-shipping-url', $response->getTargetUrl());

        $reloadedTransaction = $this->reloadPaymentTransaction($paymentTransaction->getId());
        $this->assertFalse($reloadedTransaction->isActive());
        $this->assertFalse($reloadedTransaction->isSuccessful());
    }

    public function testErrorCallbackForPendingTransactionExpressCheckout()
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(
            LoadPaymentTransactionData::PAYMENTS_PRO_EC_AUTHORIZE_PENDING_TRANSACTION
        );

        $callbackCalled = false;

        $this->getContainer()->get('event_dispatcher')->addListener(
            'oro_payment.callback.error',
            [new PaymentCallbackStubListener($callbackCalled), 'onError']
        );

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_payment_callback_error',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                ]
            )
        );

        $this->assertFalse($paymentTransaction->isActive());
        $this->assertFalse($paymentTransaction->isSuccessful());

        if (!$callbackCalled) {
            $this->fail('onError callback must be called.'
                . 'CheckCallbackRelevanceListener must not stop propagation of handling event for pending transaction');
        }

        $this->assertRedirectToFailureUrl($this->client->getResponse());
    }

    public function testErrorCallbackForPaidTransactionExpressCheckout()
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(
            LoadPaymentTransactionData::PAYMENTS_PRO_EC_AUTHORIZE_PAID_TRANSACTION
        );

        $callbackCalled = false;

        $this->getContainer()->get('event_dispatcher')->addListener(
            'oro_payment.callback.error',
            [new PaymentCallbackStubListener($callbackCalled), 'onError']
        );

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_payment_callback_error',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                ]
            )
        );

        if ($callbackCalled) {
            $this->fail('onError callback must not be called.'
                . 'CheckCallbackRelevanceListener must stop propagation of handling event');
        }

        $this->assertRedirectToSuccessUrl($this->client->getResponse());
    }

    public function testReturnCallbackForPendingTransactionExpressCheckout()
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(
            LoadPaymentTransactionData::PAYMENTS_PRO_EC_AUTHORIZE_PENDING_TRANSACTION
        );

        $callbackCalled = false;

        $this->getContainer()->get('event_dispatcher')->addListener(
            'oro_payment.callback.return',
            [new PaymentCallbackStubListener($callbackCalled), 'onReturn']
        );

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_payment_callback_return',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                ]
            )
        );

        $this->assertTrue($paymentTransaction->isActive());
        $this->assertFalse($paymentTransaction->isSuccessful());

        if (!$callbackCalled) {
            $this->fail('onReturn callback must be called.'
                . 'CheckCallbackRelevanceListener must not stop propagation of handling event for pending transaction');
        }

        // Redirect to failure url is expected here
        // because PayflowExpressCheckoutListener::onReturn doesn't handle this test transaction
        $this->assertRedirectToFailureUrl($this->client->getResponse());
    }

    public function testReturnCallbackForPaidTransactionExpressCheckout()
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(
            LoadPaymentTransactionData::PAYMENTS_PRO_EC_AUTHORIZE_PAID_TRANSACTION
        );

        $callbackCalled = false;

        $this->getContainer()->get('event_dispatcher')->addListener(
            'oro_payment.callback.return',
            [new PaymentCallbackStubListener($callbackCalled), 'onReturn']
        );

        // Repeat request with same data
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_payment_callback_return',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                ]
            )
        );

        if ($callbackCalled) {
            $this->fail('onReturn callback must not be called.'
                . 'CheckCallbackRelevanceListener must stop propagation of handling event');
        }

        $this->assertRedirectToSuccessUrl($this->client->getResponse());
    }

    private function assertRedirectToFailureUrl(?Response $response): void
    {
        $this->assertNotNull($response);
        $this->assertResponseStatusCodeEquals($response, 302);
        $this->assertInstanceOf(RedirectResponse::class, $response);

        $this->assertEquals('https://example.com/failure-url', $response->getTargetUrl());
    }

    private function assertRedirectToSuccessUrl(?Response $response): void
    {
        $this->assertNotNull($response);
        $this->assertResponseStatusCodeEquals($response, 302);
        $this->assertInstanceOf(RedirectResponse::class, $response);

        $this->assertEquals('https://example.com/success-url', $response->getTargetUrl());
    }

    private function createFieldFormatErrorPaymentTransaction(): PaymentTransaction
    {
        /** @var Channel $channel */
        $channel = $this->getReference(LoadPayPalChannelData::PAYPAL_PAYMENTS_PRO1);
        /** @var CustomerUser $frontendOwner */
        $frontendOwner = $this->getReference(LoadCustomerUserData::EMAIL);
        // A fresh order with no payment history of its own, so CheckCallbackRelevanceListener (which runs at
        // the same priority and checks the order's payment status) sees it as PENDING and does not interfere.
        $order = $this->createOrderWithNoPaymentHistory();

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setAction(PaymentMethodInterface::AUTHORIZE);
        $paymentTransaction->setAmount($order->getTotal());
        $paymentTransaction->setCurrency($order->getCurrency());
        $paymentTransaction->setEntityIdentifier($order->getId());
        $paymentTransaction->setEntityClass(Order::class);
        $paymentTransaction->setFrontendOwner($frontendOwner);
        $paymentTransaction->setPaymentMethod('paypal_payments_pro_express_checkout_' . $channel->getId());
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful(true);
        $paymentTransaction->setResponse([
            PayflowResponse::RESULT_KEY => ResponseStatusMap::FIELD_FORMAT_ERROR,
            PayflowResponse::RESPMSG_KEY => 'Field format error: 10736-A match of the Shipping Address City,'
                . ' State, and Postal Code failed.',
        ]);
        $paymentTransaction->setTransactionOptions([
            RedirectListener::FAILED_SHIPPING_ADDRESS_URL_KEY => 'https://example.com/failed-shipping-url',
        ]);

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PaymentTransaction::class);
        $em->persist($paymentTransaction);
        $em->flush();

        return $paymentTransaction;
    }

    private function createOrderWithNoPaymentHistory(): Order
    {
        /** @var Order $referenceOrder */
        $referenceOrder = $this->getReference(LoadOrders::ORDER_1);

        $order = new Order();
        $order->setOwner($referenceOrder->getOwner());
        $order->setOrganization($referenceOrder->getOrganization());
        $order->setWebsite($referenceOrder->getWebsite());
        $order->setCustomer($referenceOrder->getCustomer());
        $order->setCustomerUser($referenceOrder->getCustomerUser());
        $order->setIdentifier('BB-27826-field-format-error');
        $order->setCurrency('USD');
        $order->setSubtotal(100.0);
        $order->setTotal(100.0);

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        return $order;
    }

    private function reloadPaymentTransaction(int $id): PaymentTransaction
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(PaymentTransaction::class);
        $em->clear();

        /** @var PaymentTransaction $transaction */
        $transaction = $em->find(PaymentTransaction::class, $id);
        self::assertNotNull($transaction);

        return $transaction;
    }
}
