<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\OrderBundle\EventListener\FormViewListener;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\Account;

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
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->listener = new FormViewListener($this->translator, $this->doctrineHelper, $requestStack);
    }

    public function testOnAccountUserView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $accountUser = new AccountUser();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($accountUser);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->with('OroOrderBundle:AccountUser:orders_view.html.twig', ['entity' => $accountUser])
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->listener->onAccountUserView($event);
    }

    public function testOnAccountUserViewWithEmptyRequest()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->never())
            ->method('getScrollData');

        $this->listener->onAccountUserView($event);
    }

    public function testOnAccountView()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(1);

        $account = new Account();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($account);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->with('OroOrderBundle:Account:orders_view.html.twig', ['entity' => $account])
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->listener->onAccountView($event);
    }

    public function testOnAccountViewWithoutId()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(null);

        $account = new Account();

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference')
            ->willReturn($account);

        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->never())->method('getScrollData');

        $this->listener->onAccountView($event);
    }

    public function testOnAccountViewWithoutEntity()
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

        $this->listener->onAccountView($event);
    }

    public function testOnAccountViewWithEmptyRequest()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->never())
            ->method('getScrollData');

        $this->listener->onAccountView($event);
    }
}
