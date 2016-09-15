<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\WarehouseBundle\EventListener\ProductWarehouseFormViewListener;

class ProductWarehouseFormViewListenerTest extends \PHPUnit_Framework_TestCase
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
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ProductWarehouseFormViewListener
     */
    protected $productWarehouseFormViewListener;

    /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject * */
    protected $event;

    protected function setUp()
    {
        $this->requestStack = $this->getMock(RequestStack::class);
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productWarehouseFormViewListener = new ProductWarehouseFormViewListener(
            $this->requestStack,
            $this->doctrineHelper
        );
        $this->event = $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
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
