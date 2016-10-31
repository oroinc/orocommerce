<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\InventoryBundle\EventListener\ProductQuantityToOrderFormViewListener;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class ProductQuantityToOrderFormViewListenerTest extends FormViewListenerTestCase
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
     * @var ProductQuantityToOrderFormViewListener
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
        $this->productWarehouseFormViewListener = new ProductQuantityToOrderFormViewListener(
            $this->requestStack,
            $this->doctrineHelper,
            $this->translator
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
        $scrollData = $this->getMock(ScrollData::class);
        $this->event->expects($this->once())->method('getScrollData')->willReturn($scrollData);
        $scrollData->expects($this->once())->method('getData')->willReturn(
            ['dataBlocks' => [1 => ['title' => 'oro.product.sections.inventory.trans']]]
        );

        $this->productWarehouseFormViewListener->onProductView($this->event);
    }
}
