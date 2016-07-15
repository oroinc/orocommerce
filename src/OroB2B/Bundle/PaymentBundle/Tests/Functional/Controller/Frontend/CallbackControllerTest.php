<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData;

/**
 * @dbIsolation
 */
class CallbackControllerTest extends WebTestCase
{
    const ALLOWED_REMOTE_ADDR = '173.0.81.1';

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData']);
    }

    public function testWithoutTransactionNoErrors()
    {
        foreach (['POST', 'GET'] as $method) {
            foreach (['orob2b_payment_callback_return', 'orob2b_payment_callback_error'] as $route) {
                $this->client->request(
                    $method,
                    $this->getUrl($route, ['accessIdentifier' => 'some_key', 'accessToken' => 'some_val']),
                    [],
                    [],
                    ['REMOTE_ADDR' => self::ALLOWED_REMOTE_ADDR]
                );
            }
        }
    }

    public function testReturnAndErrorCallbacksDontChangeActiveAndSuccessful()
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(LoadPaymentTransactionData::PAYFLOW_AUTHORIZE_TRANSACTION);

        foreach (['POST', 'GET'] as $method) {
            foreach (['orob2b_payment_callback_return', 'orob2b_payment_callback_error'] as $route) {
                $this->assertCallback($paymentTransaction, $method, $route);
            }
        }
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param string $method
     * @param string $route
     */
    protected function assertCallback(PaymentTransaction $paymentTransaction, $method, $route)
    {
        $parameters = [
            'PNREF' => 'Transaction Reference ' . $method . $route,
            'RESULT' => '0',
            'SECURETOKEN' => 'SECURETOKEN',
            'SECURETOKENID' => 'SECURETOKENID',
        ];

        $expectedData = $parameters + $paymentTransaction->getRequest();
        $this->client->request(
            $method,
            $this->getUrl(
                $route,
                ['accessIdentifier' => $paymentTransaction->getAccessIdentifier()]
            ),
            $expectedData,
            [],
            ['REMOTE_ADDR' => self::ALLOWED_REMOTE_ADDR]
        );

        $objectManager = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPaymentBundle:PaymentTransaction');

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $objectManager->find($paymentTransaction->getId());

        $this->assertFalse($paymentTransaction->isActive());
        $this->assertFalse($paymentTransaction->isSuccessful());
        $this->assertEquals(
            [
                'SECURETOKEN' => 'SECURETOKEN',
                'SECURETOKENID' => 'SECURETOKENID',
            ],
            $paymentTransaction->getResponse()
        );
    }

    public function testNotifyChangeState()
    {
        $parameters = [
            'PNREF' => 'REFERENCE',
            'RESULT' => '0',
            'SECURETOKEN' => 'SECURETOKEN',
            'SECURETOKENID' => 'SECURETOKENID',
        ];

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(LoadPaymentTransactionData::PAYFLOW_AUTHORIZE_TRANSACTION);
        $this->assertFalse($paymentTransaction->isActive());
        $this->assertFalse($paymentTransaction->isSuccessful());

        $expectedData = $parameters + $paymentTransaction->getRequest();
        $this->client->request(
            'POST',
            $this->getUrl(
                'orob2b_payment_callback_notify',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ]
            ),
            $expectedData,
            [],
            ['REMOTE_ADDR' => self::ALLOWED_REMOTE_ADDR]
        );

        $objectManager = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPaymentBundle:PaymentTransaction');

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $objectManager->find($paymentTransaction->getId());

        $this->assertTrue($paymentTransaction->isActive());
        $this->assertTrue($paymentTransaction->isSuccessful());
        $this->assertEquals($expectedData, $paymentTransaction->getResponse());
    }

    public function testNotifyGetIsInvalid()
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(LoadPaymentTransactionData::PAYFLOW_AUTHORIZE_TRANSACTION);
        $this->assertFalse($paymentTransaction->isActive());
        $this->assertFalse($paymentTransaction->isSuccessful());

        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_payment_callback_notify',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ]
            ),
            [],
            [],
            ['REMOTE_ADDR' => self::ALLOWED_REMOTE_ADDR]
        );

        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 405);
    }

    public function testNotifyTokenRequired()
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(LoadPaymentTransactionData::PAYFLOW_AUTHORIZE_TRANSACTION);
        $this->assertFalse($paymentTransaction->isActive());
        $this->assertFalse($paymentTransaction->isSuccessful());

        $this->client->request(
            'POST',
            $this->getUrl(
                'orob2b_payment_callback_notify',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => '123',
                ]
            ),
            [],
            [],
            ['REMOTE_ADDR' => self::ALLOWED_REMOTE_ADDR]
        );

        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    /**
     * @return array[]
     */
    public function returnAlowedIPs()
    {
        return [
            'Paypal\'s IP address 1 should be allowed' => ['173.0.81.1'],
            'Paypal\'s IP address 2 should be allowed' => ['173.0.81.33'],
            'Paypal\'s IP address 3 should be allowed' => ['173.0.81.65'],
            'Paypal\'s IP address 4 should be allowed' => ['66.211.170.66'],
        ];
    }

    /**
     * @return array[]
     */
    public function returnNotAlowedIPs()
    {
        return [
            'Google\'s IP address 5 should not be allowed' => ['216.58.214.206'],
            'Facebook\'s IP address 6 should not be allowed' => ['173.252.120.68'],
        ];
    }

    /**
     * @dataProvider returnAlowedIPs
     * @param string $remoteAddr
     */
    public function testNotifyAllowedIPFiltering($remoteAddr)
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(LoadPaymentTransactionData::PAYFLOW_AUTHORIZE_TRANSACTION_IP_FILTER);

        $this->client->request(
            'POST',
            $this->getUrl(
                'orob2b_payment_callback_notify',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ]
            ),
            [],
            [],
            ['REMOTE_ADDR' => $remoteAddr]
        );

        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    /**
     * @dataProvider returnNotAlowedIPs
     * @param string $remoteAddr
     */
    public function testNotifyNotAllowedIPFiltering($remoteAddr)
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getReference(LoadPaymentTransactionData::PAYFLOW_AUTHORIZE_TRANSACTION_IP_FILTER);

        $this->client->request(
            'POST',
            $this->getUrl(
                'orob2b_payment_callback_notify',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ]
            ),
            [],
            [],
            ['REMOTE_ADDR' => $remoteAddr]
        );

        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 403);
    }
}
