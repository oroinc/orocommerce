<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\RFPBundle\EventListener\CustomerViewListener;
use Oro\Bundle\RFPBundle\EventListener\AbstractCustomerViewListener;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

abstract class AbstractCustomerViewListenerTest extends FormViewListenerTestCase
{
    const RENDER_HTML = 'render_html';
    const TRANSLATED_TEXT = 'translated_text';

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** @var Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject */
    protected $env;

    /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var ScrollData|\PHPUnit_Framework_MockObject_MockObject */
    protected $scrollData;

    /** * @var CustomerViewListener */
    protected $customerViewListener;

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

        $this->customerViewListener = $this->createListenerToTest();
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
            ->with($this->getCustomerViewTemplate(), ['entity' => $customer])
            ->willReturn(self::RENDER_HTML);

        $scrollData = $this->getScrollData();
        $scrollData->expects($this->once())
            ->method('addBlock')
            // translated label
            ->with($this->getCustomerLabel() . '.trans');

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
            ->with($this->getCustomerUserViewTemplate(), ['entity' => $customerUser])
            ->willReturn(self::RENDER_HTML);

        $scrollData = $this->getScrollData();
        $scrollData->expects($this->once())
            ->method('addBlock')
            // translated label
            ->with($this->getCustomerUserLabel() . '.trans');

        $scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with(null, null, self::RENDER_HTML);

        $this->event->expects($this->any())
            ->method('getScrollData')
            ->willReturn($scrollData);
        $this->customerViewListener->onCustomerUserView($this->event);
    }

    /**
     * @return string
     */
    abstract protected function getCustomerViewTemplate();

    /**
     * @return string
     */
    abstract protected function getCustomerLabel();

    /**
     * @return string
     */
    abstract protected function getCustomerUserViewTemplate();

    /**
     * @return string
     */
    abstract protected function getCustomerUserLabel();

    /**
     * @return AbstractCustomerViewListener
     */
    abstract protected function createListenerToTest();
}
