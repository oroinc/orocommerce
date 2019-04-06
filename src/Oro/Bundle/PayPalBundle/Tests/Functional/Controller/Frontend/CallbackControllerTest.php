<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalBundle\Tests\Functional\DataFixtures\LoadPaymentTransactionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CallbackControllerTest extends WebTestCase
{
    const ALLOWED_REMOTE_ADDR = '173.0.81.1';

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadPaymentTransactionData::class]);
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
            ['REMOTE_ADDR' => self::ALLOWED_REMOTE_ADDR]
        );

        $paymentTransactionRepository = $this->getContainer()->get('doctrine')
            ->getRepository('OroPaymentBundle:PaymentTransaction');

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $paymentTransactionRepository->find($paymentTransaction->getId());

        // Active flag of charge transaction after complete(notify request) action should be changed to false
        $this->assertFalse($paymentTransaction->isActive());
        $this->assertTrue($paymentTransaction->isSuccessful());
        $this->assertEquals($expectedData, $paymentTransaction->getResponse());
    }

    /**
     * @return array[]
     */
    public function returnAllowedIPs()
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
    public function returnNotAllowedIPs()
    {
        return [
            'Google\'s IP address 5 should not be allowed' => ['216.58.214.206'],
            'Facebook\'s IP address 6 should not be allowed' => ['173.252.120.68'],
        ];
    }

    /**
     * @dataProvider returnAllowedIPs
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
     * @dataProvider returnNotAllowedIPs
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
}
