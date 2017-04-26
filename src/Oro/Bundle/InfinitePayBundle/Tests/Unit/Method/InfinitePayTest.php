<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Method;

use Oro\Bundle\InfinitePayBundle\Action\ActionInterface;
use Oro\Bundle\InfinitePayBundle\Action\Registry\ActionRegistryInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Method\InfinitePay;
use Oro\Bundle\InfinitePayBundle\Method\Provider\OrderProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * {@inheritdoc}
 */
class InfinitePayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InfinitePayConfigInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var ActionRegistryInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionRegistry;

    /**
     * @var OrderProviderInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->config = $this->createMock(InfinitePayConfigInterface::class);
        $this->actionRegistry = $this->createMock(ActionRegistryInterface::class);
        $this->orderProvider = $this->createMock(OrderProviderInterface::class);
    }

    public function testSupports()
    {
        $infinitePay = new InfinitePay($this->config, $this->actionRegistry, $this->orderProvider);
        $this->assertTrue($infinitePay->supports('purchase'));
        $this->assertFalse($infinitePay->supports('unknown_method'));
    }

    public function testExecute()
    {
        /** @var ActionInterface|PHPUnit_Framework_MockObject_MockObject  $action */
        $action = $this->createMock(ActionInterface::class);
        $action
            ->expects(static::once())
            ->method('execute')
            ->willReturn(['action return value']);
        $this->actionRegistry
            ->expects(static::once())
            ->method('getActionByType')
            ->with('purchase')
            ->willReturn($action);
        $this->orderProvider = $this->createMock(OrderProviderInterface::class);
        $this->orderProvider
            ->expects(static::once())
            ->method('getDataObjectFromPaymentTransaction')
            ->willReturn(new Order());

        $infinitePay = new InfinitePay($this->config, $this->actionRegistry, $this->orderProvider);
        $this->assertEquals(['action return value'], $infinitePay->execute('purchase', new PaymentTransaction()));
    }
}
