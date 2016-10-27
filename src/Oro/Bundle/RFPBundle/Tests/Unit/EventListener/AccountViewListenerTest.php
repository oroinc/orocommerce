<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\RFPBundle\EventListener\AccountViewListener;

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

    /**
     * @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $featureChecker;

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

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->accountViewListener = new AccountViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->requestStack
        );
        $this->accountViewListener->setFeatureChecker($this->featureChecker);
        $this->accountViewListener->addFeature('rfp');
    }

    public function testOnAccountViewGetsIgnoredIfNoRequest()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->accountViewListener->onAccountView($this->event);
    }

    public function testOnAccountViewGetsIgnoredIfNoRequestId()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->accountViewListener->onAccountView($this->event);
    }

    public function testOnAccountViewGetsIgnoredIfNoEntityFound()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
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
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
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
            ->with('OroRFPBundle:Account:rfp_view.html.twig', ['entity' => $account]);
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
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
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
            ->with('OroRFPBundle:AccountUser:rfp_view.html.twig', ['entity' => $accountUser]);
        $scrollData = $this->getScrollData();
        $scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with(null, null, self::RENDER_HTML);
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($scrollData);
        $this->accountViewListener->onAccountUserView($this->event);
    }

    public function testOnAccountViewDisabledFeature()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->accountViewListener->onAccountView($this->event);
    }

    public function testOnAccountUserViewDisabledFeature()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->accountViewListener->onAccountUserView($this->event);
    }
}
