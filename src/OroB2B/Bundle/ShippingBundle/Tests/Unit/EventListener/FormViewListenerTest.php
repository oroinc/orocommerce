<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\EventListener\FormViewListener;
use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FormViewListenerTest extends FormViewListenerTestCase
{
    /** @var Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** @var ShippingOriginProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingOriginProvider;

    /** @var FormViewListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->shippingOriginProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Provider\ShippingOriginProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getRequest();

        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $this->listener = new FormViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->shippingOriginProvider,
            $this->requestStack
        );
    }

    public function testOnWarehouseViewWithoutRequest()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);

        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())->method('getScrollData');

        $this->listener->onWarehouseView($event);
    }

    public function testOnWarehouseViewWithEmptyRequest()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);

        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())->method('getScrollData');

        $this->listener->onWarehouseView($event);
    }

    public function testOnWarehouseViewWithoutWarehouse()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);
        $this->request->expects($this->once())->method('get')->with('id')->willReturn(42);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BWarehouseBundle:Warehouse', 42)
            ->willReturn(null);

        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())->method('getScrollData');

        $this->listener->onWarehouseView($event);
    }

    public function testOnWarehouseViewWithEmptyShippingOrigin()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);
        $this->request->expects($this->once())->method('get')->with('id')->willReturn(42);

        $warehouse = new Warehouse();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BWarehouseBundle:Warehouse', 42)
            ->willReturn($warehouse);

        $shippingOrigin = new ShippingOrigin();

        $this->shippingOriginProvider->expects($this->once())
            ->method('getShippingOriginByWarehouse')
            ->with($warehouse)
            ->willReturn($shippingOrigin);

        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())->method('getScrollData');

        $this->listener->onWarehouseView($event);
    }

    public function testOnWarehouseView()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);
        $this->request->expects($this->once())->method('get')->with('id')->willReturn(42);

        $warehouse = new Warehouse();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BWarehouseBundle:Warehouse', 42)
            ->willReturn($warehouse);

        $shippingOrigin = new ShippingOrigin(['region' => 'data']);

        $this->shippingOriginProvider->expects($this->once())
            ->method('getShippingOriginByWarehouse')
            ->with($warehouse)
            ->willReturn($shippingOrigin);

        $renderedHtml = 'rendered_html';

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $twig */
        $twig = $this->getMockBuilder('\Twig_Environment')->disableOriginalConstructor()->getMock();
        $twig->expects($this->once())
            ->method('render')
            ->with('OroB2BShippingBundle:Warehouse:shipping_origin_view.html.twig', ['entity' => $shippingOrigin])
            ->willReturn($renderedHtml);

        $event = new BeforeListRenderEvent($twig, $this->getScrollData());

        $this->listener->onWarehouseView($event);

        $scrollData = $event->getScrollData()->getData();
        $this->assertEquals(
            [$renderedHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnWarehouseEdit()
    {
        $renderedHtml = 'rendered_html';

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $twig */
        $twig = $this->getMockBuilder('\Twig_Environment')->disableOriginalConstructor()->getMock();
        $twig->expects($this->once())->method('render')->willReturn($renderedHtml);

        $event = new BeforeListRenderEvent($twig, $this->getScrollData());

        $this->listener->onWarehouseEdit($event);

        $scrollData = $event->getScrollData()->getData();
        $this->assertEquals(
            [$renderedHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnProductViewWithoutRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())
            ->method('getScrollData');

        $this->listener->onProductView($event);
    }

    public function testOnProductViewWithEmptyRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())
            ->method('getScrollData');

        $this->listener->onProductView($event);
    }

    public function testOnProductViewWithoutProduct()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);
        $this->request->expects($this->once())->method('get')->with('id')->willReturn(42);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BProductBundle:Product', 42)
            ->willReturn(null);

        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())->method('getScrollData');

        $this->listener->onProductView($event);
    }

    public function testOnProductViewWithEmptyShippingOptions()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(47);

        $product = new Product();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BProductBundle:Product', 47)
            ->willReturn($product);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['product' => 47])
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with('OroB2BShippingBundle:ProductShippingOptions')
            ->willReturn($mockRepo);

        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())->method('getScrollData');

        $this->listener->onProductView($event);
    }

    public function testOnProductView()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(47);

        $product = new Product();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BProductBundle:Product', 47)
            ->willReturn($product);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['product' => 47])
            ->willReturn(
                [
                    new ProductShippingOptions(),
                    new ProductShippingOptions(),
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with('OroB2BShippingBundle:ProductShippingOptions')
            ->willReturn($mockRepo);

        $renderedHtml = 'rendered_html';

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $twig */
        $twig = $this->getMockBuilder('\Twig_Environment')->disableOriginalConstructor()->getMock();
        $twig->expects($this->once())
            ->method('render')
            ->with(
                'OroB2BShippingBundle:Product:shipping_options_view.html.twig',
                [
                    'entity' => $product,
                    'shippingOptions' => [new ProductShippingOptions(), new ProductShippingOptions()]
                ]
            )
            ->willReturn($renderedHtml);

        $event = new BeforeListRenderEvent($twig, $this->getScrollData());

        $this->listener->onProductView($event);

        $scrollData = $event->getScrollData()->getData();
        $this->assertEquals(
            [$renderedHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnProductEdit()
    {
        $renderedHtml = 'rendered_html';

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $twig */
        $twig = $this->getMockBuilder('\Twig_Environment')->disableOriginalConstructor()->getMock();
        $twig->expects($this->once())->method('render')->willReturn($renderedHtml);

        $event = new BeforeListRenderEvent($twig, $this->getScrollData());

        $this->listener->onProductEdit($event);

        $scrollData = $event->getScrollData()->getData();
        $this->assertEquals(
            [$renderedHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    /**
     * @return ScrollData
     */
    protected function getScrollData()
    {
        return new ScrollData([
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => []
                        ]
                    ]
                ]
            ]
        ]);
    }
}
