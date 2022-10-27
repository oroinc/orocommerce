<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Factory\OrderPaymentContextFactory;
use Oro\Bundle\OrderBundle\Factory\SupportsOrderPaymentContextFactory;

class SupportsOrderPaymentContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var OrderPaymentContextFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $orderPaymentContextFactory;

    /**
     * @var SupportsOrderPaymentContextFactory
     */
    protected $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->orderPaymentContextFactory = $this->createMock(OrderPaymentContextFactory::class);

        $this->factory = new SupportsOrderPaymentContextFactory(
            $this->doctrineHelper,
            $this->orderPaymentContextFactory
        );
    }

    public function testCreate()
    {
        $orderId = 1;
        $order = $this->createMock(Order::class);

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntity')
            ->with(Order::class, $orderId)
            ->willReturn($order);

        $this->orderPaymentContextFactory
            ->expects(static::once())
            ->method('create')
            ->with($order);

        $this->factory->create(Order::class, $orderId);
    }

    /**
     * @dataProvider supportsWithSupportedClassDataProvider
     *
     * @param int $entityId
     * @param bool $expected
     */
    public function testSupportsWithSupportedClass($entityId, $expected)
    {
        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntity')
            ->willReturnMap([
                [Order::class, 1, new \stdClass()],
                [Order::class, 2, null]
            ]);

        $actual = $this->factory->supports(Order::class, $entityId);
        static::assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function supportsWithSupportedClassDataProvider()
    {
        return [
            'with existing order' => [1, true],
            'with non-existing order' => [2, false],
        ];
    }

    public function testSupportsWithUnsupportedClass()
    {
        $actual = $this->factory->supports(\stdClass::class, 1);
        static::assertFalse($actual);
    }
}
