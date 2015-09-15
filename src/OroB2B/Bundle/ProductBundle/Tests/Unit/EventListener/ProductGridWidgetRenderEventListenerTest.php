<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;

use OroB2B\Bundle\ProductBundle\Event\ProductGridWidgetRenderEvent;
use OroB2B\Bundle\ProductBundle\EventListener\ProductGridWidgetRenderEventListener;

class ProductGridWidgetRenderEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductGridWidgetRenderEventListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestParameterBagFactory */
    protected $requestParameterBagFactory;

    protected function setUp()
    {
        $this->requestParameterBagFactory = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductGridWidgetRenderEventListener($this->requestParameterBagFactory);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->requestParameterBagFactory);
    }

    public function testOnWidgetRender()
    {
        $gridParams = ['i' => 1, 'p' => 25, 's' => []];
        $event = new ProductGridWidgetRenderEvent(['grid' => ['removed' => 'params']]);

        $this->requestParameterBagFactory->expects($this->once())->method('createParameters')
            ->willReturn(new ParameterBag($gridParams));
        $this->listener->onWidgetRender($event);

        $eventParams = $event->getWidgetRouteParameters();
        $this->assertArrayHasKey('grid', $eventParams);
        $this->assertSame($gridParams, $eventParams['grid']);
    }
}
