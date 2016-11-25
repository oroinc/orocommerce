<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\InventoryBundle\EventListener\ProductInventoryThresholdFormViewListener;

class ProductInventoryThresholdFormViewListenerTest extends FormViewListenerTestCase
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
     * @var ProductInventoryThresholdFormViewListener
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
        $this->productWarehouseFormViewListener = new ProductInventoryThresholdFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
        $this->event = $this->getBeforeListRenderEventMock();
    }

    public function testOnProductViewIgnoredIfNoProductId()
    {
        $this->doctrine->expects($this->never())->method('getManagerForClass');
        $this->productWarehouseFormViewListener->onProductView($this->event);
    }

    public function testOnProductViewIgnoredIfNoProductFound()
    {
        $this->em->expects($this->once())->method('getReference')->willReturn(null);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);
        $this->request->expects($this->once())->method('get')->willReturn('1');
        $this->event->expects($this->never())->method('getEnvironment');
        $this->productWarehouseFormViewListener->onProductView($this->event);
    }

    public function testOnProductViewRendersAndAddsSubBlock()
    {
        $this->request->expects($this->once())->method('get')->willReturn('1');
        $product = new Product();
        $this->em->expects($this->once())->method('getReference')->willReturn($product);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);
        $env = $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock();
        $this->event->expects($this->once())->method('getEnvironment')->willReturn($env);
        $this->event->expects($this->once())->method('getScrollData')->willReturn($this->getMock(ScrollData::class));

        $this->productWarehouseFormViewListener->onProductView($this->event);
    }
}
