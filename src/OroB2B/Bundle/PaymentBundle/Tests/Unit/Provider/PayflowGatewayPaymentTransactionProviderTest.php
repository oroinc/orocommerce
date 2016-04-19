<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Provider;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Provider\PayflowGatewayPaymentTransactionProvider;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class PayflowGatewayPaymentTransactionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayflowGatewayPaymentTransactionProvider */
    protected $provider;

    /** @var PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTransactionProvider;

    protected function setUp()
    {
        $this->paymentTransactionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PayflowGatewayPaymentTransactionProvider($this->paymentTransactionProvider);
    }

    public function testGetZeroAmountTransaction()
    {
        $object = new \stdClass();

        $transaction = new PaymentTransaction();

        $this->paymentTransactionProvider->expects($this->once())->method('getPaymentTransaction')
            ->with(
                $this->equalTo($object),
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->equalTo(
                        [
                            'amount' => 0,
                            'active' => true,
                            'successful' => true,
                            'action' => 'authorize',
                            'paymentMethod' => 'payflow_type',
                        ]
                    )
                ),
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->equalTo(['id' => 'DESC'])
                )
            )
            ->willReturn($transaction);

        $this->assertSame(
            $transaction,
            $this->provider->getZeroAmountTransaction($object, 'payflow_type')
        );
    }
}
