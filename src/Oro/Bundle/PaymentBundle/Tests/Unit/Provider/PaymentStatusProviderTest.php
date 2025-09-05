<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculatorInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentStatusProviderTest extends TestCase
{
    protected PaymentTransactionProvider|MockObject $paymentTransactionProvider;
    protected TotalProcessorProvider|MockObject $totalProcessorProvider;
    protected PaymentStatusCalculatorInterface|MockObject $paymentStatusCalculator;

    protected PaymentStatusProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->totalProcessorProvider = $this->createMock(TotalProcessorProvider::class);
        $this->paymentStatusCalculator = $this->createMock(PaymentStatusCalculatorInterface::class);

        $this->provider = new PaymentStatusProvider(
            $this->paymentTransactionProvider,
            $this->totalProcessorProvider
        );
    }

    public function testGetPaymentStatusWithCalculator(): void
    {
        $entity = new \stdClass();
        $expectedStatus = PaymentStatuses::PAID_IN_FULL;

        $this->paymentStatusCalculator->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($entity)
            ->willReturn($expectedStatus);

        $this->paymentTransactionProvider->expects(self::never())
            ->method('getPaymentTransactions');

        $this->totalProcessorProvider->expects(self::never())
            ->method('getTotal');

        $this->provider->setPaymentStatusCalculator($this->paymentStatusCalculator);

        self::assertEquals($expectedStatus, $this->provider->getPaymentStatus($entity));
    }

    /**
     * @dataProvider statusDataProvider
     */
    public function testStatus(array $transactions, float $amount, string $expectedStatus)
    {
        $object = new \stdClass();

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransactions')
            ->with($object)
            ->willReturn($transactions);

        $total = new Subtotal();
        $total->setAmount($amount);

        $this->totalProcessorProvider->expects($this->any())
            ->method('getTotal')
            ->with($object)
            ->willReturn($total);

        $this->assertEquals($expectedStatus, $this->provider->getPaymentStatus($object));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function statusDataProvider(): array
    {
        $sourceReference = 'source_reference';
        $sourceTransaction = (new PaymentTransaction())
            ->setSuccessful(true)
            ->setActive(false)
            ->setReference($sourceReference)
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setAmount(0);

        return [
            'full if has successful capture' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PAID_IN_FULL,
            ],
            'partial if has successful capture but less amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setAmount(50),
                ],
                100,
                PaymentStatuses::PAID_PARTIALLY,
            ],
            'declined if has unsuccessful capture' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::DECLINED,
            ],
            'full if has successful charge' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PAID_IN_FULL,
            ],
            'partial if has successful charge but less amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(40),
                ],
                100,
                PaymentStatuses::PAID_PARTIALLY,
            ],
            'declined if has unsuccessful charge' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::DECLINED,
            ],
            'full if has successful purchase' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PAID_IN_FULL,
            ],
            'partial  if has successful purchase but less amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(60),
                ],
                100,
                PaymentStatuses::PAID_PARTIALLY,
            ],
            'declined if has unsuccessful purchase' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::DECLINED,
            ],
            'authorize if has active and successful authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::AUTHORIZED,
            ],
            'pending if has active but unsuccessful authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PENDING,
            ],
            'pending if has successful but not active authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PENDING,
            ],
            'pending if source validation transaction clone' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setReference($sourceReference)
                        ->setSourcePaymentTransaction($sourceTransaction)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PENDING,
            ],
            'authorized if source validation transaction but not cloned' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setReference('own_reference')
                        ->setSourcePaymentTransaction($sourceTransaction)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::AUTHORIZED,
            ],
            'declined if has unsuccessful and not active authorize' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::DECLINED,
            ],
            'full has higher priority than declined' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PAID_IN_FULL,
            ],
            'invoiced has higher priority than authorized' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::INVOICE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::INVOICED,
            ],
            'full has higher priority than authorized' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PAID_IN_FULL,
            ],
            'partial has higher priority than authorized' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(40),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PAID_PARTIALLY,
            ],
            'partial has higher priority than declined' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(40),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PAID_PARTIALLY,
            ],
            'authorize has higher priority than declined' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::AUTHORIZED,
            ],
            'full has top priority' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CHARGE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                ],
                100,
                PaymentStatuses::PAID_IN_FULL,
            ],
            'full with few successful' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(40),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(60),
                ],
                100,
                PaymentStatuses::PAID_IN_FULL,
            ],
            'full with few successful and amount more than required' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(70),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(60),
                ],
                100,
                PaymentStatuses::PAID_IN_FULL,
            ],
            'partial with few successful' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(40),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::PURCHASE)
                        ->setAmount(20),
                ],
                100,
                PaymentStatuses::PAID_PARTIALLY,
            ],
            'pending if has validation' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::VALIDATE)
                        ->setAmount(0),
                ],
                100,
                PaymentStatuses::PENDING,
            ],
            'pending if has not any transactions' => [
                [],
                100,
                PaymentStatuses::PENDING,
            ],
            'partially cancelled amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setAmount(50),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CANCEL)
                        ->setAmount(10),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CANCEL)
                        ->setAmount(30),
                ],
                100,
                PaymentStatuses::CANCELED_PARTIALLY
            ],
            'partially authorized amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(50.001),
                    (new PaymentTransaction())
                        ->setSuccessful(false)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(50),
                ],
                100.001,
                PaymentStatuses::AUTHORIZED_PARTIALLY
            ],
            're-authorized amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CANCEL)
                        ->setAmount(100),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(true)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(100.003),
                ],
                100.002,
                PaymentStatuses::AUTHORIZED
            ],
            'partially canceled amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::AUTHORIZE)
                        ->setAmount(50),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CANCEL)
                        ->setAmount(50)
                ],
                50,
                PaymentStatuses::CANCELED
            ],
            'partially refunded amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setAmount(50.00),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::REFUND)
                        ->setAmount(30.00)
                ],
                50.00,
                PaymentStatuses::REFUNDED_PARTIALLY
            ],
            'fully refunded amount' => [
                [
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::CAPTURE)
                        ->setAmount(50.00),
                    (new PaymentTransaction())
                        ->setSuccessful(true)
                        ->setActive(false)
                        ->setAction(PaymentMethodInterface::REFUND)
                        ->setAmount(50.00)
                ],
                50.00,
                PaymentStatuses::REFUNDED
            ]
        ];
    }
}
