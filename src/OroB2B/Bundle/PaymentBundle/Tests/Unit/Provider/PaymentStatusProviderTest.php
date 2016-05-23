<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Provider;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class PaymentStatusProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTransactionProvider;

    /** @var PaymentStatusProvider */
    protected $provider;

    protected function setUp()
    {
        $this->paymentTransactionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PaymentStatusProvider($this->paymentTransactionProvider);
    }

    /**
     * @param array $transactions
     * @param string $expectedStatus
     *
     * @dataProvider statusDataProvider
     */
    public function testStatus(array $transactions, $expectedStatus)
    {
        $this->paymentTransactionProvider->expects($this->once())->method('getPaymentTransactions')
            ->willReturn($transactions);

        $this->assertEquals($expectedStatus, $this->provider->getPaymentStatus(new \stdClass()));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function statusDataProvider()
    {
        return [
            'full if has successful capture' => [
                [
                    (new PaymentTransaction())->setSuccessful(true)->setAction(PaymentMethodInterface::CAPTURE),
                ],
                PaymentStatusProvider::FULL,
            ],
            'declined if has unsuccessful capture' => [
                [
                    (new PaymentTransaction())->setSuccessful(false)->setAction(PaymentMethodInterface::CAPTURE),
                ],
                PaymentStatusProvider::DECLINED,
            ],
            'full if has successful charge' => [
                [
                    (new PaymentTransaction())->setSuccessful(true)->setAction(PaymentMethodInterface::CHARGE),
                ],
                PaymentStatusProvider::FULL,
            ],
            'declined if has unsuccessful charge' => [
                [
                    (new PaymentTransaction())->setSuccessful(false)->setAction(PaymentMethodInterface::CHARGE),
                ],
                PaymentStatusProvider::DECLINED,
            ],
            'full if has successful purchase' => [
                [
                    (new PaymentTransaction())->setSuccessful(true)->setAction(PaymentMethodInterface::PURCHASE),
                ],
                PaymentStatusProvider::FULL,
            ],
            'declined if has unsuccessful purchase' => [
                [
                    (new PaymentTransaction())->setSuccessful(false)->setAction(PaymentMethodInterface::PURCHASE),
                ],
                PaymentStatusProvider::DECLINED,
            ],
            'authorize if has active and successful authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                PaymentStatusProvider::AUTHORIZED,
            ],
            'pending if has active but unsuccessful authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                PaymentStatusProvider::PENDING,
            ],
            'pending if has successful but not active authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                PaymentStatusProvider::PENDING,
            ],
            'declined if has unsuccessful and not active authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                PaymentStatusProvider::DECLINED,
            ],
            'full has higher priority than declined' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setAction(PaymentMethodInterface::CHARGE),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                PaymentStatusProvider::FULL,
            ],
            'full has higher priority than authorized' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setAction(PaymentMethodInterface::CHARGE),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                PaymentStatusProvider::FULL,
            ],
            'authorize has higher priority than declined' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                PaymentStatusProvider::AUTHORIZED,
            ],
            'full has top prioiry' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setAction(PaymentMethodInterface::CHARGE),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE),
                ],
                PaymentStatusProvider::FULL,
            ],
        ];
    }
}
