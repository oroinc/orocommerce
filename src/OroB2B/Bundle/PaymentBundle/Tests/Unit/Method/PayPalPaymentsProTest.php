<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Method\PayPalPaymentsPro;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayPalPaymentsProTest extends AbstractPayflowGatewayTest
{
    /** @var PayPalPaymentsPro */
    protected $method;

    protected function setUp()
    {
        $this->setMocks();
        $this->method = new PayPalPaymentsPro($this->gateway, $this->configManager, $this->router);
    }

    public function testIsEnabled()
    {
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_ENABLED_KEY, true);
        $this->assertTrue($this->method->isEnabled());
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'purchase successful' => [
                [
                    'gatewayAction' => Option\Transaction::SALE,
                    'sourceTransactionData' => [],
                    'transactionData' => [
                        'action' => 'purchase',
                        'request' => [],
                        'amount' => 100.0,
                        'currency' => 'USD',
                    ],
                    'configs' => [
                        Configuration::PAYPAL_PAYMENTS_PRO_VENDOR_KEY => 'test_vendor',
                        Configuration::PAYPAL_PAYMENTS_PRO_USER_KEY => 'test_user',
                        Configuration::PAYPAL_PAYMENTS_PRO_PASSWORD_KEY => 'test_password',
                        Configuration::PAYPAL_PAYMENTS_PRO_PARTNER_KEY => 'test_partner',
                        Configuration::PAYPAL_PAYMENTS_PRO_TEST_MODE_KEY => true,
                        Configuration::PAYPAL_PAYMENTS_PRO_PAYMENT_ACTION_KEY => 'charge',
                    ],
                    'requestOptions' => [
                        'VENDOR' => 'test_vendor',
                        'USER' => 'test_user',
                        'PWD' => 'test_password',
                        'PARTNER' => 'test_partner',
                        'CREATESECURETOKEN' => 1,
                        'AMT' => 100.0,
                        'SILENTTRAN' => 1,
                        'TENDER' => 'C',
                        'CURRENCY' => 'USD',
                        'RETURNURL' => 'orob2b_payment_callback_return',
                        'ERRORURL' => 'orob2b_payment_callback_error',
                    ],
                    'responseData' => [
                        'RESULT' => '0',
                        'PNREF' => 'test_reference',
                        'RESPMSG' => 'test_message',
                    ],
                ],
                [
                    'formAction' => 'test_form_action',
                ]
            ],
        ];
    }
}
