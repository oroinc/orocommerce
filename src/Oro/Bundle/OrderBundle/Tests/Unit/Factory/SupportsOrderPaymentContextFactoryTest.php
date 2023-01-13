<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Factory\OrderPaymentContextFactory;
use Oro\Bundle\OrderBundle\Factory\SupportsOrderPaymentContextFactory;

class SupportsOrderPaymentContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var OrderPaymentContextFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $orderPaymentContextFactory;

    /** @var SupportsOrderPaymentContextFactory */
    private $factory;

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

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(Order::class, $orderId)
            ->willReturn($order);

        $this->orderPaymentContextFactory->expects(self::once())
            ->method('create')
            ->with($order);

        $this->factory->create(Order::class, $orderId);
    }

    /**
     * @dataProvider supportsWithSupportedClassDataProvider
     */
    public function testSupportsWithSupportedClass(int $entityId, bool $expected)
    {
        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->willReturnMap([
                [Order::class, 1, new \stdClass()],
                [Order::class, 2, null]
            ]);

        $actual = $this->factory->supports(Order::class, $entityId);
        self::assertSame($expected, $actual);
    }

    public function supportsWithSupportedClassDataProvider(): array
    {
        return [
            'with existing order' => [1, true],
            'with non-existing order' => [2, false],
        ];
    }

    public function testSupportsWithUnsupportedClass()
    {
        $actual = $this->factory->supports(\stdClass::class, 1);
        self::assertFalse($actual);
    }
}
