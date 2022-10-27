<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Oro\Bundle\PaymentBundle\Condition\PaymentTransactionWasCanceled;
use Oro\Bundle\PaymentBundle\Condition\PaymentTransactionWasCharged;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class PaymentTransactionWasCanceledTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTransactionRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionRepository;

    /**
     * @var PaymentTransactionWasCharged
     */
    private $condition;

    protected function setUp(): void
    {
        $this->transactionRepository = $this->createMock(PaymentTransactionRepository::class);

        $this->condition = new PaymentTransactionWasCanceled($this->transactionRepository);
    }

    public function testGetName()
    {
        $this->assertEquals('payment_transaction_was_canceled', $this->condition->getName());
    }

    public function testInitializeException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "transaction" option');
        $this->condition->initialize([]);
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            PaymentTransactionWasCanceled::class,
            $this->condition->initialize([
                'transaction' => new PaymentTransaction()
            ])
        );
    }

    public function testEvaluate()
    {
        $context = $this->createMock(PaymentContextInterface::class);

        $this->transactionRepository->expects(static::once())
            ->method('findSuccessfulRelatedTransactionsByAction')
            ->willReturn([]);

        $this->condition->initialize([
            'transaction' => new PaymentTransaction(),
            'context' => $context
        ]);

        $this->assertFalse($this->condition->evaluate($context));
    }
}
