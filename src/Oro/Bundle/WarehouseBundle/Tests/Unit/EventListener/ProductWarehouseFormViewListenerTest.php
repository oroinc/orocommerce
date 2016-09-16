<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\WarehouseBundle\EventListener\ProductWarehouseFormViewListener;

class ProductWarehouseFormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var ProductWarehouseFormViewListener
     */
    protected $productWarehouseFormViewListener;

    /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject * */
    protected $event;

    protected function setUp()
    {
        parent::setUp();
        $this->requestStack = $this->getMock(RequestStack::class);
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->productWarehouseFormViewListener = new ProductWarehouseFormViewListener(
            $this->requestStack,
            $this->doctrineHelper
        );
        $this->event = $this->getBeforeListRenderEventMock();
    }

    public function testOnProductViewIgnoredIfNoProductId()
    {
        $this->doctrineHelper->expects($this->never())->method('getEntityReference');
        $this->productWarehouseFormViewListener->onProductView($this->event);
    }

    public function testOnProductViewIgnoredIfNoProductFound()
    {
        $this->request->expects($this->once())->method('get')->willReturn('1');
        $this->event->expects($this->never())->method('getEnvironment');
        $this->productWarehouseFormViewListener->onProductView($this->event);
    }

    public function testOnProductViewRendersAndAddsSubBlock()
    {
        $this->request->expects($this->once())->method('get')->willReturn('1');
        $product = new Product();
        $this->doctrineHelper->expects($this->once())->method('getEntityReference')->willReturn($product);
        $env = $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock();
        $this->event->expects($this->once())->method('getEnvironment')->willReturn($env);
        $this->event->expects($this->once())->method('getScrollData')->willReturn($this->getMock(ScrollData::class));

        $this->productWarehouseFormViewListener->onProductView($this->event);
    }
}
