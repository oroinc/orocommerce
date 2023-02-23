<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Layout\DataProvider\OrderMultiShippingLayoutProvider;

class OrderMultiShippingLayoutProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $multiShippingConfigProvider;

    /** @var OrderMultiShippingLayoutProvider */
    private $layoutDataProvider;

    protected function setUp(): void
    {
        $this->multiShippingConfigProvider = $this->createMock(ConfigProvider::class);

        $this->layoutDataProvider = new OrderMultiShippingLayoutProvider($this->multiShippingConfigProvider);
    }

    /**
     * @dataProvider getDisplaySubOrdersAvailableDataProvider
     */
    public function testGetDisplaySubOrdersAvailable(Order $order, bool $expected)
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isShowSubordersInOrderHistoryEnabled')
            ->willReturn(true);

        $this->assertEquals($expected, $this->layoutDataProvider->getDisplaySubOrdersAvailable($order));
    }

    public function getDisplaySubOrdersAvailableDataProvider(): array
    {
        $orderWithSuborders = new Order();
        $orderWithSuborders->addSubOrder(new Order());

        return [
            'Order with suborders' => [
                'order' => $orderWithSuborders,
                'expected' => true
            ],
            'Order without suborders' => [
                'order' => new Order(),
                'expected' => false
            ]
        ];
    }

    public function testGetDisplaySubOrdersAvailableIfConfigDisabled()
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isShowSubordersInOrderHistoryEnabled')
            ->willReturn(false);

        $this->assertFalse($this->layoutDataProvider->getDisplaySubOrdersAvailable(new Order()));
    }
}
