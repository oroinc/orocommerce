<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;
use Oro\Bundle\ProductBundle\Event\ProductGridWidgetRenderEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductGridWidgetRenderEventListener;

class ProductGridWidgetRenderEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestParameterBagFactory */
    private $requestParameterBagFactory;

    /** @var ProductGridWidgetRenderEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->requestParameterBagFactory = $this->createMock(RequestParameterBagFactory::class);

        $this->listener = new ProductGridWidgetRenderEventListener($this->requestParameterBagFactory);
    }

    public function testOnWidgetRender()
    {
        $gridParams = ['i' => 1, 'p' => 25, 's' => []];
        $event = new ProductGridWidgetRenderEvent(['grid' => ['removed' => 'params']]);

        $this->requestParameterBagFactory->expects($this->once())
            ->method('createParameters')
            ->willReturn(new ParameterBag($gridParams));
        $this->listener->onWidgetRender($event);

        $eventParams = $event->getWidgetRouteParameters();
        $this->assertArrayHasKey('grid', $eventParams);
        $this->assertSame($gridParams, $eventParams['grid']);
    }
}
