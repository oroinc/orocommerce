<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\RFPBundle\EventListener\FormViewListener;

class FormViewListenerTest extends \PHPUnit_Framework_TestCase
{
    const RENDER_HTML = 'test';

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** * @var FormViewListener */
    protected $formViewListener;

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
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);
        $this->env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event->expects($this->any())
            ->method('getEnvironment')
            ->willReturn($this->env);
        $this->scrollData = $this->getMockBuilder('Oro\Bundle\UIBundle\View\ScrollData')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event->expects($this->any())
            ->method('getScrollData')
            ->willReturn($this->scrollData);

        $this->env->expects($this->any())
            ->method('render')
            ->willReturn(self::RENDER_HTML);

        $this->formViewListener = new FormViewListener($this->translator, $this->doctrineHelper, $this->requestStack);
    }

    public function testOnAccountViewGetsIgnoredIfNoRequest()
    {
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->formViewListener->onAccountView($this->event);
    }

    public function testOnAccountViewGetsIgnoredIfNoRequestId()
    {
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->formViewListener->onAccountView($this->event);
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
        $this->formViewListener->onAccountView($this->event);
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
        $this->scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with(null, null, self::RENDER_HTML);
        $this->formViewListener->onAccountView($this->event);
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
        $this->scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with(null, null, self::RENDER_HTML);
        $this->formViewListener->onAccountUserView($this->event);
    }
}
