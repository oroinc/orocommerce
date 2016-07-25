<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SEOBundle\EventListener\ProductFormViewListener;

class ProductFormViewListenerTest extends BaseFormViewListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->listener = new ProductFormViewListener($this->requestStack, $this->translator, $this->doctrineHelper);
    }

    public function testOnProductView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $product = new Product();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getEnvironmentForView($product);
        $event = $this->getEventForView($env);

        $this->listener->onProductView($event);
    }

    public function testOnProductEdit()
    {
        $env = $this->getEnvironmentForEdit();
        $event = $this->getEventForEdit($env);

        $this->listener->onProductEdit($event);
    }

    public function testOnProductViewInvalidId()
    {
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference');

        $this->listener->onProductView($event);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn('string');

        $this->listener->onProductView($event);
    }

    public function testOnProductViewEmptyProduct()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(null);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $this->listener->onProductView($event);
    }
}
