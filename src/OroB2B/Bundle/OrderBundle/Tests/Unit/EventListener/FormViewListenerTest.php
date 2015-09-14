<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\OrderBundle\EventListener\FormViewListener;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class FormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var FormViewListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $listener = new FormViewListener($this->translator, $this->doctrineHelper);
        $this->listener = $listener;
    }

    public function testOnAccountUserView()
    {
        $request = $this->getRequest();
        $request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $this->listener->setRequest($request);

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
            ->with('OroB2BOrderBundle:AccountUser:orders_view.html.twig', ['entity' => $accountUser])
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
        $request = $this->getRequest();
        $request->expects($this->any())->method('get')->with('id')->willReturn(1);

        $this->listener->setRequest($request);

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
            ->with('OroB2BOrderBundle:Account:orders_view.html.twig', ['entity' => $account])
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->listener->onAccountView($event);
    }

    public function testOnAccountViewWithoutId()
    {
        $request = $this->getRequest();
        $request->expects($this->any())->method('get')->with('id')->willReturn(null);

        $this->listener->setRequest($request);

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
        $request = $this->getRequest();
        $request->expects($this->any())->method('get')->with('id')->willReturn(1);

        $this->listener->setRequest($request);

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
