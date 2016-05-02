<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\EventListener\ProductFormViewListener;

class ProductFormViewListenerTest extends FormViewListenerTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestStack */
    protected $requestStack;

    /** @var ProductFormViewListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->request = $this->getRequest();

        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $this->listener = new ProductFormViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->requestStack
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
