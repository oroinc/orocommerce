<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Oro\Bundle\PaymentBundle\Condition\PaymentTransactionWasRefunded;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentTransactionWasRefundedTest extends TestCase
{
    private PaymentTransactionRepository|MockObject $transactionRepository;
    private PaymentTransactionWasRefunded $condition;

    protected function setUp(): void
    {
        $this->transactionRepository = $this->createMock(PaymentTransactionRepository::class);

        $this->condition = new PaymentTransactionWasRefunded($this->transactionRepository);
    }

    public function testGetName()
    {
        $this->assertEquals('payment_transaction_was_refunded', $this->condition->getName());
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
            PaymentTransactionWasRefunded::class,
            $this->condition->initialize([
                'transaction' => new PaymentTransaction()
            ])
        );
    }

    /**
     * @dataProvider foundTransactionsDataProvider
     */
    public function testEvaluate(array $foundTransactions, bool $expected)
    {
        $transaction = new PaymentTransaction();
        $context = $this->createMock(PaymentContextInterface::class);

        $this->transactionRepository->expects(static::once())
            ->method('findSuccessfulRelatedTransactionsByAction')
            ->with($transaction, PaymentMethodInterface::REFUND)
            ->willReturn($foundTransactions);

        $this->condition->initialize([
            'transaction' => $transaction,
            'context' => $context
        ]);

        $this->assertSame($expected, $this->condition->evaluate($context));
    }

    public function foundTransactionsDataProvider(): \Generator
    {
        yield [
            [new PaymentTransaction()],
            true
        ];

        yield [
            [],
            false
        ];
    }
}
