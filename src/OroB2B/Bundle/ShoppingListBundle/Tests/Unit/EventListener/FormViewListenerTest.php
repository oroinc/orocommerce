<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\EventListener\FormViewListener;

class FormViewListenerTest extends FormViewListenerTestCase
{
    /** @var FormViewListener */
    protected $listener;

    /** @var  RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
    protected $request;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->request = $this->getRequest();

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->listener = new FormViewListener($requestStack, $this->translator, $this->doctrineHelper);
    }

    public function testFrontendProductViewWithoutRequest()
    {
        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())->method($this->anything());
        $this->listener->onFrontendProductView($event);
    }

    public function testFrontendProductViewWithoutId()
    {
        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())->method($this->anything());

        $this->request->expects($this->once())->method('get')->willReturn(null);

        $this->listener->onFrontendProductView($event);
    }

    public function testFrontendProductViewWithoutProduct()
    {
        $id = 1;

        $event = $this->getBeforeListRenderEventMock();
        $event->expects($this->never())->method($this->anything());

        $this->request->expects($this->once())->method('get')->willReturn($id);

        $this->doctrineHelper->expects($this->once())->method('getEntityReference')
            ->with($this->isType('string'), $id)->willReturn(null);

        $this->listener->onFrontendProductView($event);
    }

    public function testFrontendProductView()
    {
        $id = 1;
        $product = new Product();

        $event = $this->getBeforeListRenderEvent();

        $this->request->expects($this->once())->method('get')->willReturn($id);

        $this->doctrineHelper->expects($this->once())->method('getEntityReference')
            ->with($this->isType('string'), $id)->willReturn($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with(
                'OroB2BShoppingListBundle:Product/Frontend:view.html.twig',
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->arrayHasKey('productId')
                )
            )
            ->willReturn('html');

        $event->expects($this->once())->method('getEnvironment')->willReturn($environment);

        $this->listener->onFrontendProductView($event);
    }
}
