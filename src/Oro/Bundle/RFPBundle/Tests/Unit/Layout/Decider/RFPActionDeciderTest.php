<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Layout\Decider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\RFPBundle\Event\FormSubmitCheckEvent;
use Oro\Bundle\RFPBundle\Layout\Decider\RFPActionDecider;

class RFPActionDeciderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var RFPActionDecider
     */
    protected $decider;

    public function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->decider = new RFPActionDecider($this->eventDispatcher, $this->requestStack);
    }

    public function testShouldFormSubmitWithErrorsReturnFalseIfNoListeners()
    {
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(false);
        $this->assertFalse($this->decider->shouldFormSubmitWithErrors());
    }

    public function testShouldFormSubmitWithErrorsReturnsEventValue()
    {
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('testName');
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName, FormSubmitCheckEvent $event) {
                    $this->assertEquals('rfp.form_submit_check.testName', $eventName);
                    $event->setShouldSubmitOnError(true);
                }
            );
        $this->assertTrue($this->decider->shouldFormSubmitWithErrors());
    }
}
