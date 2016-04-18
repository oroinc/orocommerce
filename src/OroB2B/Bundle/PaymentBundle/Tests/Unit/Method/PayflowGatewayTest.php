<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayflowGatewayTest extends AbstractPayflowGatewayTest
{
    /** @var PayflowGateway */
    protected $method;

    protected function setUp()
    {
        $this->setMocks();
        $this->method = new PayflowGateway($this->gateway, $this->configManager, $this->router);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown action "wrong_action"
     */
    public function testExecuteException()
    {
        $transaction = new PaymentTransaction();
        $transaction->setAction('wrong_action');

        $this->method->execute($transaction);
    }

    public function testIsEnabled()
    {
        $this->setConfig($this->once(), Configuration::PAYFLOW_GATEWAY_ENABLED_KEY, true);
        $this->assertTrue($this->method->isEnabled());
    }

    public function testGetType()
    {
        $this->assertEquals(PayflowGateway::TYPE, $this->method->getType());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeDataProvider()
    {
        return [
            'authorize successful' => [
                [
                    'gatewayAction' => Option\Transaction::AUTHORIZATION,
                    'sourceTransactionData' => [],
                    'transactionData' => [
                        'action' => 'authorize',
                        'request' => [],
                    ],
                    'configs' => [],
                    'requestOptions' => [
                        'VENDOR' => 'test_vendor',
                        'USER' => 'test_user',
                        'PWD' => 'test_password',
                        'PARTNER' => 'test_partner',
                    ],
                    'responseData' => [
                        'RESULT' => '0',
                        'PNREF' => 'test_reference',
                    ],
                ],
                []
            ],
            'charge successful' => [
                [
                    'gatewayAction' => Option\Transaction::SALE,
                    'sourceTransactionData' => [],
                    'transactionData' => [
                        'action' => 'charge',
                        'request' => [],
                    ],
                    'configs' => [],
                    'requestOptions' => [
                        'VENDOR' => 'test_vendor',
                        'USER' => 'test_user',
                        'PWD' => 'test_password',
                        'PARTNER' => 'test_partner',
                    ],
                    'responseData' => [
                        'RESULT' => '0',
                        'PNREF' => 'test_reference',
                    ],
                ],
                []
            ],
            'capture successful' => [
                [
                    'gatewayAction' => Option\Transaction::DELAYED_CAPTURE,
                    'sourceTransactionData' => [
                        'reference' => 'test_reference',
                        'request' => ['TENDER' => 'source_tender'],
                    ],
                    'transactionData' => [
                        'action' => 'capture',
                        'request' => [],
                    ],
                    'configs' => [],
                    'requestOptions' => [
                        'VENDOR' => 'test_vendor',
                        'USER' => 'test_user',
                        'PWD' => 'test_password',
                        'PARTNER' => 'test_partner',
                        'TENDER' => 'source_tender',
                        'ORIGID' => 'test_reference',
                    ],
                    'responseData' => [
                        'RESULT' => '0',
                        'PNREF' => 'test_reference',
                        'RESPMSG' => 'test_message',
                    ],
                ],
                [
                    'message' => 'test_message',
                    'successful' => true,
                ]
            ],
            'capture without source' => [
                [
                    'gatewayAction' => Option\Transaction::DELAYED_CAPTURE,
                    'sourceTransactionData' => [],
                    'transactionData' => [
                        'action' => 'capture',
                        'request' => [],
                    ],
                    'configs' => [],
                    'requestOptions' => [],
                    'responseData' => [],
                ],
                []
            ],
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
                        Configuration::PAYFLOW_GATEWAY_PAYMENT_ACTION_KEY => 'charge',
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
