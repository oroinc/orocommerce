<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\EventListener\Callback\PayflowListener;

class PayflowListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayflowListener */
    protected $listener;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    protected function setUp()
    {
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PayflowListener($this->session);
    }

    /**
     * @param array $data
     * @param array $paymentTransactionData
     * @param array $expectedPaymentTransactionData
     *
     * @dataProvider callbackDataProvider
     */
    public function testOnCallback(
        array $data,
        array $paymentTransactionData = [],
        array $expectedPaymentTransactionData = []
    ) {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse($paymentTransactionData);

        $event = new CallbackReturnEvent($data);
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onCallback($event);
        $this->assertEquals($expectedPaymentTransactionData, $paymentTransaction->getResponse());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function callbackDataProvider()
    {
        return [
            'data without token' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                ],
            ],
            'payment transaction data without token' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
            ],
            'token id not match' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID1',
                ],
                [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID1',
                ],
            ],
            'token not match' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                [
                    'SECURETOKEN' => 'SECURETOKEN1',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                [
                    'SECURETOKEN' => 'SECURETOKEN1',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
            ],
            'token match not ordered' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                [
                    'SECURETOKENID' => 'SECURETOKENID',
                    'SECURETOKEN' => 'SECURETOKEN',
                ],
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKENID' => 'SECURETOKENID',
                    'SECURETOKEN' => 'SECURETOKEN',
                ],
            ],
            'token match ordered' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
            ],
            'test data overridden by request and without loss' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                    'key' => 'request',
                    'key2' => 'request',
                ],
                [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                    'key' => 'database',
                ],
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                    'key' => 'request',
                    'key2' => 'request',
                ],
            ],
        ];
    }
}
