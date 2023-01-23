<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\Factory\CompositeSupportsEntityPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class TransactionPaymentContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var CompositeSupportsEntityPaymentContextFactory|\PHPUnit\Framework\MockObject\MockObject*/
    private $compositeFactory;

    /** @var TransactionPaymentContextFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->compositeFactory = $this->createMock(CompositeSupportsEntityPaymentContextFactory::class);

        $this->factory = new TransactionPaymentContextFactory($this->compositeFactory);
    }

    public function testCreate()
    {
        $entityClass = \stdClass::class;
        $entityId = 1;

        $transaction = $this->createMock(PaymentTransaction::class);
        $transaction->expects(self::exactly(2))
            ->method('getEntityClass')
            ->willReturn($entityClass);
        $transaction->expects(self::exactly(2))
            ->method('getEntityIdentifier')
            ->willReturn($entityId);

        $this->compositeFactory->expects(self::once())
            ->method('supports')
            ->with($entityClass, $entityId)
            ->willReturn(true);

        $this->compositeFactory->expects(self::once())
            ->method('create')
            ->with($entityClass, $entityId);

        $this->factory->create($transaction);
    }

    public function testCreateWhenNotSupported()
    {
        $entityClass = \stdClass::class;
        $entityId = 1;

        $transaction = $this->createMock(PaymentTransaction::class);
        $transaction->expects(self::once())
            ->method('getEntityClass')
            ->willReturn($entityClass);
        $transaction->expects(self::once())
            ->method('getEntityIdentifier')
            ->willReturn($entityId);

        $this->compositeFactory->expects(self::once())
            ->method('supports')
            ->with($entityClass, $entityId)
            ->willReturn(false);

        $this->compositeFactory->expects(self::never())
            ->method('create');

        $actual = $this->factory->create($transaction);

        self::assertNull($actual);
    }
}
