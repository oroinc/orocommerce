<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\EventListener\CustomerViewListener;

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

    /** @var WebsiteProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $websiteProvider;

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

        $this->customerViewListener = new CustomerViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->requestStack
        );

        $this->websiteProvider = $this->getMockBuilder(WebsiteProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $website = new Website();
        $this->websiteProvider->method('getWebsites')->willReturn([$website]);
    }

    public function testOnCustomerViewGetsIgnoredIfNoRequest()
    {
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerViewGetsIgnoredIfNoRequestId()
    {
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerViewGetsIgnoredIfNoEntityFound()
    {
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
            ->with('OroSaleBundle:Customer:quote_view.html.twig', ['entity' => $customer]);
        $scrollData = $this->getScrollData();
        $scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with(null, null, self::RENDER_HTML);
        $this->event->expects($this->any())
            ->method('getScrollData')
            ->willReturn($scrollData);
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerUserViewCreatesScrollBlock()
    {
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
            ->with('OroSaleBundle:CustomerUser:quote_view.html.twig', ['entity' => $customerUser]);
        $scrollData = $this->getScrollData();
        $scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with(null, null, self::RENDER_HTML);
        $this->event->expects($this->any())
            ->method('getScrollData')
            ->willReturn($scrollData);
        $this->customerViewListener->onCustomerUserView($this->event);
    }
}
