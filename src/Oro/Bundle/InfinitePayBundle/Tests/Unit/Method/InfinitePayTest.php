<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Method;

use Oro\Bundle\InfinitePayBundle\Action\Registry\ActionRegistry;
use Oro\Bundle\InfinitePayBundle\Action\Registry\ActionRegistryInterface;
use Oro\Bundle\InfinitePayBundle\Action\Reserve;
use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfig;
use Oro\Bundle\InfinitePayBundle\Method\InfinitePay;
use Oro\Bundle\InfinitePayBundle\Method\Provider\CheckoutOrderProvider;
use Oro\Bundle\InfinitePayBundle\Method\Provider\OrderProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * {@inheritdoc}
 */
class InfinitePayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InfinitePayConfig
     */
    protected $config;

    /**
     * @var ActionRegistryInterface
     */
    protected $actionRegistry;

    /**
     * @var OrderProviderInterface
     */
    protected $orderProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->config = $this->getMockBuilder(InfinitePayConfig::class)->disableOriginalConstructor()->getMock();
        $this->actionRegistry = $this->getMockBuilder(ActionRegistry::class)->disableOriginalConstructor()->getMock();
        $reserveAction = $this->getMockBuilder(Reserve::class)->disableOriginalConstructor()->getMock();
        $this->actionRegistry->method('getActionByType')->with('purchase')->willReturn($reserveAction);
        $this->orderProvider = $this
            ->getMockBuilder(CheckoutOrderProvider::class)->disableOriginalConstructor()->getMock();
        $this->orderProvider->method('getDataObjectFromPaymentTransaction')->willReturn(new Order());
    }

    public function testSupports()
    {
        $infinitePay = new InfinitePay($this->config, $this->actionRegistry, $this->orderProvider);
        $this->assertTrue($infinitePay->supports('purchase'));
        $this->assertFalse($infinitePay->supports('unknown_method'));
    }
}
