<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\OrderBundle\EventListener\FormViewListener;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class FormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var FormViewListener
     */
    protected $listener;

    /** @var  Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->request = $this->getRequest();
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->listener = new FormViewListener($this->translator, $this->doctrineHelper, $requestStack);
    }

    public function testOnCustomerUserView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $customerUser = new CustomerUser();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customerUser);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->with('OroOrderBundle:CustomerUser:orders_view.html.twig', ['entity' => $customerUser])
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->listener->onCustomerUserView($event);
    }

    public function testOnCustomerUserViewWithEmptyRequest()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->never())
            ->method('getScrollData');

        $this->listener->onCustomerUserView($event);
    }

    public function testOnCustomerView()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(1);

        $customer = new Customer();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customer);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->with('OroOrderBundle:Customer:orders_view.html.twig', ['entity' => $customer])
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->listener->onCustomerView($event);
    }

    public function testOnCustomerViewWithoutId()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(null);

        $customer = new Customer();

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference')
            ->willReturn($customer);

        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->never())->method('getScrollData');

        $this->listener->onCustomerView($event);
    }

    public function testOnCustomerViewWithoutEntity()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(1);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(null);

        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->never())->method('getScrollData');

        $this->listener->onCustomerView($event);
    }

    public function testOnCustomerViewWithEmptyRequest()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->never())
            ->method('getScrollData');

        $this->listener->onCustomerView($event);
    }
}
