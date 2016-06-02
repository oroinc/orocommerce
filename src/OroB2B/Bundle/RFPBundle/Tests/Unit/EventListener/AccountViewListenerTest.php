<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\RFPBundle\EventListener\AccountViewListener;

class AccountViewListenerTest extends FormViewListenerTestCase
{
    const RENDER_HTML = 'test';

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** * @var AccountViewListener */
    protected $accountViewListener;

    /** @var Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject */
    protected $env;

    /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var ScrollData|\PHPUnit_Framework_MockObject_MockObject */
    protected $scrollData;

    protected function setUp()
    {
        parent::setUp();

        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getBeforeListRenderEventMock();
        $this->event->expects($this->any())
            ->method('getEnvironment')
            ->willReturn($this->env);

        $this->env->expects($this->any())
            ->method('render')
            ->willReturn(self::RENDER_HTML);

        $this->accountViewListener = new AccountViewListener($this->translator, $this->doctrineHelper, $this->requestStack);
    }

    public function testOnAccountViewGetsIgnoredIfNoRequest()
    {
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->accountViewListener->onAccountView($this->event);
    }

    public function testOnAccountViewGetsIgnoredIfNoRequestId()
    {
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->accountViewListener->onAccountView($this->event);
    }

    public function testOnAccountViewGetsIgnoredIfNoEntityFound()
    {
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(1);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(null);

        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->accountViewListener->onAccountView($this->event);
    }

    public function testOnAccountViewCreatesScrollBlock()
    {
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(1);
        $account = new Account();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($account);
        $this->event->expects($this->once())
            ->method('getEnvironment');
        $this->env->expects($this->once())
            ->method('render')
            ->with('OroB2BRFPBundle:Account:rfp_view.html.twig', ['entity' => $account]);
        $scrollData = $this->getScrollData();
        $scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with(null, null, self::RENDER_HTML);
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($scrollData);
        $this->accountViewListener->onAccountView($this->event);
    }

    public function testOnAccountUserViewCreatesScrollBlock()
    {
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(1);
        $accountUser = new AccountUser();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($accountUser);
        $this->event->expects($this->once())
            ->method('getEnvironment');
        $this->env->expects($this->once())
            ->method('render')
            ->with('OroB2BRFPBundle:AccountUser:rfp_view.html.twig', ['entity' => $accountUser]);
        $scrollData = $this->getScrollData();
        $scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with(null, null, self::RENDER_HTML);
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($scrollData);
        $this->accountViewListener->onAccountUserView($this->event);
    }
}
