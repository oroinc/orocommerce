<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\InventoryBundle\EventListener\ProductDecrementQuantityFormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;

class ProductDecrementQuantityFormViewListenerTest extends FormViewListenerTestCase
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
     * @var ProductDecrementQuantityFormViewListener
     */
    protected $productDecrementQuantityFormViewListener;

    /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject * */
    protected $event;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    protected function setUp()
    {
        parent::setUp();
        $this->requestStack = new RequestStack();
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack->push($this->request);
        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productDecrementQuantityFormViewListener = new ProductDecrementQuantityFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
        $this->event = $this->getBeforeListRenderEventMock();
    }

    public function testOnProductViewIgnoredIfNoProductId()
    {
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');
        $this->productDecrementQuantityFormViewListener->onProductView($this->event);
    }

    public function testOnProductViewIgnoredIfNoProductFound()
    {
        $this->em->expects($this->once())
            ->method('getReference')
            ->willReturn(null);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('1');
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->productDecrementQuantityFormViewListener->onProductView($this->event);
    }

    public function testOnProductViewRendersAndAddsSubBlock()
    {
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('1');
        $product = new Product();
        $this->em->expects($this->once())
            ->method('getReference')
            ->willReturn($product);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);
        $env = $this->getMockBuilder(\Twig_Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);
        $scrollData = $this->getMock(ScrollData::class);
        $scrollData->expects($this->once())
            ->method('addSubBlockData');
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($scrollData);
        $scrollData->expects($this->once())
            ->method('getData')
            ->wilLReturn(
                [
                    ScrollData::DATA_BLOCKS => [1 => [ScrollData::TITLE => 'oro.product.sections.inventory.trans']],
                ]
            );
        $this->productDecrementQuantityFormViewListener->onProductView($this->event);
    }
}
