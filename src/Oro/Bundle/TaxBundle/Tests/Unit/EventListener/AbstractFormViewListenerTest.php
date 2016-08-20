<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\TaxBundle\EventListener\AbstractFormViewListener;

abstract class AbstractFormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var AbstractFormViewListener
     */
    protected $listener;

    /** @var Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    protected function setUp()
    {
        parent::setUp();

        $this->request = $this->getRequest();
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
    }

    protected function tearDown()
    {
        unset($this->request, $this->requestStack);

        parent::tearDown();
    }

    /**
     * @return AbstractFormViewListener
     */
    abstract public function getListener();

    public function testOnViewInvalidId()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference');

        $this->getListener()->onView($event);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn('string');

        $this->getListener()->onView($event);
    }

    public function testOnViewEmpty()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(null);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityRepository')
            ->willReturn(null);

        $this->getListener()->onView($event);
    }

    public function testEmptyRequest()
    {
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn(null);

        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->never())->method($this->anything());
        $this->request->expects($this->never())->method($this->anything());

        $this->getListener()->onView($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ScrollData
     */
    protected function getScrollData()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ScrollData $scrollData */
        $scrollData = $this->getMock('Oro\Bundle\UIBundle\View\ScrollData');

        $scrollData->expects($this->once())
            ->method('addSubBlockData');

        return $scrollData;
    }
}
