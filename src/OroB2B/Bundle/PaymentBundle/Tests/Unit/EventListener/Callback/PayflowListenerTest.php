<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\EventListener\Callback\PayflowListener;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseStatusMap;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

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

    protected function tearDown()
    {
        unset($this->listener, $this->session);
    }

    /**
     * @param array $data
     * @param string $paymentTransactionAction
     * @param array $paymentTransactionData
     * @param array $expectedPaymentTransactionData
     *
     * @dataProvider callbackDataProvider
     */
    public function testOnCallback(
        array $data,
        $paymentTransactionAction = PaymentMethodInterface::AUTHORIZE,
        array $paymentTransactionData = [],
        array $expectedPaymentTransactionData = []
    ) {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setAction($paymentTransactionAction);
        $paymentTransaction->setResponse($paymentTransactionData);

        $event = new CallbackReturnEvent($data);
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onCallback($event);

        $response = $paymentTransaction->getResponse();
        $this->assertEquals($expectedPaymentTransactionData, $response);

        if ($expectedPaymentTransactionData && $this->checkTokens($data, $paymentTransactionData)) {
            $this->assertEquals($data['PNREF'], $paymentTransaction->getReference());
            if ($paymentTransactionAction === PaymentMethodInterface::AUTHORIZE) {
                $this->assertEquals($data['RESULT'] === ResponseStatusMap::APPROVED, $paymentTransaction->isActive());
            } else {
                $this->assertFalse($paymentTransaction->isActive());
            }
        }
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
                PaymentMethodInterface::AUTHORIZE
            ],
            'payment transaction data without token' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                PaymentMethodInterface::AUTHORIZE
            ],
            'token id not match' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                PaymentMethodInterface::AUTHORIZE,
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
                PaymentMethodInterface::AUTHORIZE,
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
                PaymentMethodInterface::AUTHORIZE,
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
                PaymentMethodInterface::AUTHORIZE,
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
            'transaction action is not authorize' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                PaymentMethodInterface::CAPTURE,
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
                PaymentMethodInterface::AUTHORIZE,
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

    /**
     * @param array $data
     * @param array $paymentTransactionData
     * @param array $expectedPaymentTransactionData
     * @param bool $expectedWarning
     *
     * @dataProvider onErrorDataProvider
     */
    public function testOnError(
        array $data,
        array $paymentTransactionData,
        array $expectedPaymentTransactionData,
        $expectedWarning
    ) {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setResponse($paymentTransactionData)
            ->setAction(PaymentMethodInterface::AUTHORIZE);

        $event = new CallbackReturnEvent($data);
        $event->setPaymentTransaction($paymentTransaction);

        $flashBag = $this->getMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');
        $flashBag->expects($expectedWarning ? $this->once() : $this->never())
            ->method('set')
            ->with('warning', 'orob2b.payment.result.token_expired');

        $this->session->expects($expectedWarning ? $this->once() : $this->never())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->listener->onError($event);

        $response = $paymentTransaction->getResponse();
        $this->assertEquals($expectedPaymentTransactionData, $response);

        if ($expectedPaymentTransactionData && $this->checkTokens($data, $paymentTransactionData)) {
            $this->assertEquals($data['PNREF'], $paymentTransaction->getReference());
            $this->assertEquals($data['RESULT'] === ResponseStatusMap::APPROVED, $paymentTransaction->isActive());
        }
    }

    /**
     * @return array
     */
    public function onErrorDataProvider()
    {
        return [
            'token expired' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => ResponseStatusMap::SECURE_TOKEN_EXPIRED,
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => ResponseStatusMap::SECURE_TOKEN_EXPIRED,
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                true
            ],
            'token match ordered' => [
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => ResponseStatusMap::APPROVED,
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                [
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                [
                    'PNREF' => 'Transaction Reference',
                    'RESULT' => ResponseStatusMap::APPROVED,
                    'SECURETOKEN' => 'SECURETOKEN',
                    'SECURETOKENID' => 'SECURETOKENID',
                ],
                false
            ],
        ];
    }

    /**
     * @param array $actualData
     * @param array $expectedData
     * @return bool
     */
    protected function checkTokens($actualData, $expectedData)
    {
        $keys = [Option\SecureToken::SECURETOKEN, Option\SecureTokenIdentifier::SECURETOKENID];
        $keys = array_flip($keys);
        $dataToken = array_intersect_key($actualData, $keys);
        $transactionDataToken = array_intersect_key($expectedData, $keys);

        return $dataToken == $transactionDataToken;
    }
}
