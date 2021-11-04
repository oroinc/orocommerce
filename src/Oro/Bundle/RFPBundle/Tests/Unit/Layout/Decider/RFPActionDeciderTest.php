<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Layout\Decider;

use Oro\Bundle\RFPBundle\Event\FormSubmitCheckEvent;
use Oro\Bundle\RFPBundle\Layout\Decider\RFPActionDecider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RFPActionDeciderTest extends \PHPUnit\Framework\TestCase
{
    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    private Request|\PHPUnit\Framework\MockObject\MockObject $request;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private RFPActionDecider $decider;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects(self::any())->method('getMainRequest')->willReturn($this->request);
        $this->decider = new RFPActionDecider($this->eventDispatcher, $this->requestStack);
    }

    public function testShouldFormSubmitWithErrorsReturnFalseIfNoListeners(): void
    {
        $this->eventDispatcher->expects(self::once())
            ->method('hasListeners')
            ->willReturn(false);
        self::assertFalse($this->decider->shouldFormSubmitWithErrors());
    }

    public function testShouldFormSubmitWithErrorsReturnsEventValue(): void
    {
        $this->eventDispatcher->expects(self::once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->request->expects(self::once())
            ->method('get')
            ->willReturn('testName');
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                function (FormSubmitCheckEvent $event, string $eventName) {
                    self::assertEquals('rfp.form_submit_check.testName', $eventName);
                    $event->setShouldSubmitOnError(true);

                    return $event;
                }
            );
        self::assertTrue($this->decider->shouldFormSubmitWithErrors());
    }
}
