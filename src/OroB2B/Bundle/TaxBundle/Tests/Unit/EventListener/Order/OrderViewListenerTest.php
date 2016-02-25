<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener\Order;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\EventListener\Order\OrderViewListener;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderViewListenerTest extends FormViewListenerTestCase
{
    /** @var OrderViewListener */
    protected $listener;

    /** @var Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $settingsProvider;

    /** @var TaxManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $taxManager;

    protected function setUp()
    {
        parent::setUp();

        $this->request = $this->getRequest();
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $this->settingsProvider = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()->getMock();

        $this->taxManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxManager')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new OrderViewListener(
            $this->settingsProvider,
            $this->taxManager,
            $this->requestStack,
            $this->doctrineHelper,
            $this->translator,
            'OroB2B\Bundle\OrderBundle\Entity\Order'
        );
    }

    public function testOnViewTaxationDisabled()
    {
        $event = $this->getBeforeListRenderEventMock();

        $event->expects($this->never())->method($this->anything());
        $this->doctrineHelper->expects($this->never())->method($this->anything());

        $this->listener->onView($event);
    }

    public function testOnViewTaxationWithoutRequest()
    {
        $event = $this->getBeforeListRenderEventMock();

        $this->settingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);

        $event->expects($this->never())->method($this->anything());
        $this->doctrineHelper->expects($this->never())->method($this->anything());

        $this->listener->onView($event);
    }

    public function testOnViewTaxationWithoutId()
    {
        $event = $this->getBeforeListRenderEventMock();

        $this->settingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn('string');

        $event->expects($this->never())->method($this->anything());
        $this->doctrineHelper->expects($this->never())->method($this->anything());

        $this->listener->onView($event);
    }

    public function testOnViewWithEmptyResult()
    {
        $event = $this->getBeforeListRenderEventMock();

        $this->settingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $order = new Order();
        $this->doctrineHelper->expects($this->once())->method('getEntityReference')->willReturn($order);

        $result = new Result();
        $this->taxManager->expects($this->once())->method('loadTax')->with($order)->willReturn($result);

        $event->expects($this->never())->method('getEnvironment');

        $this->listener->onView($event);
    }

    public function testOnView()
    {
        $event = $this->getBeforeListRenderEvent();

        $this->settingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $order = new Order();
        $this->doctrineHelper->expects($this->once())->method('getEntityReference')->willReturn($order);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $result = new Result([Result::TOTAL => new ResultElement()]);
        $this->taxManager->expects($this->once())->method('loadTax')->with($order)->willReturn($result);

        $env->expects($this->once())
            ->method('render')
            ->with('OroB2BTaxBundle::view.html.twig', ['result' => $result])
            ->willReturn('rendered');

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->listener->onView($event);
    }
}
