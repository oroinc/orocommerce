<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Oro\Bundle\PaymentBundle\Condition\PaymentTransactionWasCharged;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;

class PaymentTransactionWasChargedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentTransactionRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionRepository;

    /**
     * @var PaymentTransactionWasCharged
     */
    private $condition;

    protected function setUp()
    {
        $this->transactionRepository = $this->createMock(PaymentTransactionRepository::class);

        $this->condition = new PaymentTransactionWasCharged($this->transactionRepository);
    }

    public function testGetName()
    {
        $this->assertEquals('payment_transaction_was_charged', $this->condition->getName());
    }

    public function testInitializeException()
    {
        $this->expectExceptionMessage('Missing "transaction" option');

        $this->condition->initialize([]);
    }

    public function testInitialize()
    {
        $this->condition->initialize([
            'transaction' => new PaymentTransaction()
        ]);
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
