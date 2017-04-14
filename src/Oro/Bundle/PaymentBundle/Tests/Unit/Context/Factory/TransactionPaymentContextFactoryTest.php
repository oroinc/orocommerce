<?php

namespace Oro\Bundle\PaymentBundle\Test\Unit\Context\Factory;

use Oro\Bundle\PaymentBundle\Context\Factory\CompositeSupportsEntityPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class TransactionPaymentContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CompositeSupportsEntityPaymentContextFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $compositeFactory;

    /**
     * @var TransactionPaymentContextFactory
     */
    protected $factory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->compositeFactory = $this->createMock(CompositeSupportsEntityPaymentContextFactory::class);

        $this->factory = new TransactionPaymentContextFactory($this->compositeFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function testCreate()
    {
        $entityClass = \stdClass::class;
        $entityId = 1;

        $transaction = $this->createMock(PaymentTransaction::class);
        $transaction
            ->expects(static::once())
            ->method('getEntityClass')
            ->willReturn($entityClass);
        $transaction
            ->expects(static::once())
            ->method('getEntityIdentifier')
            ->willReturn($entityId);

        $this->compositeFactory
            ->expects(static::once())
            ->method('create')
            ->with($entityClass, $entityId);

        return $this->factory->create($transaction);
    }
}
