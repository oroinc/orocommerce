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
    protected function setUp()
    {
        $this->initClient();
    }

    public function testWithoutTransactionNoErrors()
    {
        foreach (['POST', 'GET'] as $method) {
            foreach (['orob2b_payment_callback_return', 'orob2b_payment_callback_error'] as $route) {
                $this->client->request(
                    $method,
                    $this->getUrl($route, ['accessIdentifier' => 'some_key', 'accessToken' => 'some_val'])
                );
            }
        }
    }

    public function testCallbacks()
    {
        $this->loadFixtures(['OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData']);

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
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ]
            ),
            $expectedData
        );

        $objectManager = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPaymentBundle:PaymentTransaction');

        $paymentTransaction = $objectManager->find($paymentTransaction->getId());

        $this->assertEquals(true, $paymentTransaction->isActive());
        $this->assertEquals('Transaction Reference ' . $method . $route, $paymentTransaction->getReference());
        $this->assertEquals($expectedData, $paymentTransaction->getResponse());
    }
}
