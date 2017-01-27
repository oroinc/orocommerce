<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\RFPBundle\EventListener\CustomerViewListener;

class CustomerViewListenerTest extends FormViewListenerTestCase
{
    const RENDER_HTML = 'test';

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** * @var CustomerViewListener */
    protected $customerViewListener;

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

        $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $this->requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
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

        $this->customerViewListener = new CustomerViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->requestStack
        );
        $this->customerViewListener->setFeatureChecker($this->featureChecker);
        $this->customerViewListener->addFeature('rfp');
    }

    public function testOnCustomerViewGetsIgnoredIfNoRequest()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerViewGetsIgnoredIfNoRequestId()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerViewGetsIgnoredIfNoEntityFound()
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
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerViewCreatesScrollBlock()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(1);
        $customer = new Customer();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customer);
        $this->event->expects($this->once())
            ->method('getEnvironment');
        $this->env->expects($this->once())
            ->method('render')
            ->with('OroRFPBundle:Customer:rfp_view.html.twig', ['entity' => $customer]);
        $scrollData = $this->getScrollData();
        $scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with(null, null, self::RENDER_HTML);
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($scrollData);
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerUserViewCreatesScrollBlock()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(1);
        $customerUser = new CustomerUser();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customerUser);
        $this->event->expects($this->once())
            ->method('getEnvironment');
        $this->env->expects($this->once())
            ->method('render')
            ->with('OroRFPBundle:CustomerUser:rfp_view.html.twig', ['entity' => $customerUser]);
        $scrollData = $this->getScrollData();
        $scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with(null, null, self::RENDER_HTML);
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($scrollData);
        $this->customerViewListener->onCustomerUserView($this->event);
    }

    public function testOnCustomerViewDisabledFeature()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerUserViewDisabledFeature()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->customerViewListener->onCustomerUserView($this->event);
    }
}
