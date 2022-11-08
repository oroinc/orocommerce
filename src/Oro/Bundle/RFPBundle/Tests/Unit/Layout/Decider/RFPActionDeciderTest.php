<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Layout\Decider;

use Oro\Bundle\RFPBundle\Event\FormSubmitCheckEvent;
use Oro\Bundle\RFPBundle\Layout\Decider\RFPActionDecider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RFPActionDeciderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var RFPActionDecider */
    private $decider;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->request = $this->createMock(Request::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->decider = new RFPActionDecider($this->eventDispatcher, $requestStack);
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
            ->willReturnCallback(function (FormSubmitCheckEvent $event, string $eventName) {
                self::assertEquals('rfp.form_submit_check.testName', $eventName);
                $event->setShouldSubmitOnError(true);

                return $event;
            });
        self::assertTrue($this->decider->shouldFormSubmitWithErrors());
    }
}
