<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Layout\Decider;

use Oro\Bundle\RFPBundle\Event\FormSubmitCheckEvent;
use Oro\Bundle\RFPBundle\Layout\Decider\RFPActionDecider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RFPActionDeciderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestStack;

    /**
     * @var Request|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $request;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    /**
     * @var RFPActionDecider
     */
    protected $decider;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects($this->any())->method('getMasterRequest')->willReturn($this->request);
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
                function (FormSubmitCheckEvent $event, string $eventName) {
                    $this->assertEquals('rfp.form_submit_check.testName', $eventName);
                    $event->setShouldSubmitOnError(true);
                }
            );
        $this->assertTrue($this->decider->shouldFormSubmitWithErrors());
    }
}
