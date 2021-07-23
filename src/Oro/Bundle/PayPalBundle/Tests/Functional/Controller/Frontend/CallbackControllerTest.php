<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalBundle\Tests\Functional\Controller\Frontend\Stub\PaymentCallbackStubListener;
use Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class CallbackControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

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

    /**
     * @return array[]
     */
    public function allowedIPsDataProvider()
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
     *
     * @param string $remoteAddress
     */
    public function testNotifyAllowedIPFiltering($remoteAddress)
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

    /**
     * @return array[]
     */
    public function notAllowedIPsDataProvider()
    {
        return [
            'Google\'s IP address 5 should not be allowed' => ['216.58.214.206'],
            'Facebook\'s IP address 6 should not be allowed' => ['173.252.120.68'],
        ];
    }

    /**
     * @dataProvider notAllowedIPsDataProvider
     *
     * @param string $remoteAddress
     */
    public function testNotifyNotAllowedIPFiltering($remoteAddress)
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

        $this->assertRedirectToFailureUrl($this->client->getResponse());
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

        // Redirect to failure url is expected here
        // because PayflowExpressCheckoutListener::onReturn doesn't handle this test transaction
        $this->assertRedirectToFailureUrl($this->client->getResponse());
    }

    /**
     * @param RedirectResponse|Response|null $response
     */
    private function assertRedirectToFailureUrl(?Response $response)
    {
        $this->assertNotNull($response);
        $this->assertResponseStatusCodeEquals($response, 302);
        $this->assertInstanceOf(RedirectResponse::class, $response);

        $this->assertEquals('https://example.com/failure-url', $response->getTargetUrl());
    }
}
