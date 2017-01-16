<?php

namespace Oro\Tests\Unit\Component\Action\Condition;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

use Oro\Component\Action\Condition\ExtendableCondition;
use Oro\Component\Action\Exception\ExtendableEventNameMissingException;

class ExtendableConditionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var FlashBag|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $flashBag;

    /**
     * @var ExtendableCondition
     */
    protected $extendableCondition;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->extendableCondition = new ExtendableCondition($this->eventDispatcher, $this->flashBag);
    }

    public function testIsConditionAllowedIsTrueIfNoEvents()
    {
        $options = ['events' => []];
        $this->extendableCondition->initialize($options);

        $this->assertTrue($this->extendableCondition->isConditionAllowed([]));
    }

    public function testIsConditionAllowedIsTrueIfNoEventListeners()
    {
        $options = ['events' => ['aaa']];
        $this->extendableCondition->initialize($options);
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(false);

        $this->assertTrue($this->extendableCondition->isConditionAllowed([]));
    }

    /**
     * @param array $options
     */
    private function expectsDispatchWithErrors(array $options)
    {
        $this->extendableCondition->initialize($options);
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName, $event) {
                    $event->addError('First error');
                    $event->addError('Second error');
                }
            );
    }

    public function testIsConditionAllowedIsFalseIfEventHasErrors()
    {
        $this->expectsDispatchWithErrors(['events' => ['aaa']]);
        $this->assertFalse($this->extendableCondition->isConditionAllowed([]));
    }

    public function testIsConditionAllowedNotShowErrorsWhenShowErrorsIsFalse()
    {
        $this->expectsDispatchWithErrors(['events' => ['aaa'], 'showErrors' => false]);

        $this->flashBag
            ->expects($this->never())
            ->method('add');

        $this->assertFalse($this->extendableCondition->isConditionAllowed([]));
    }

    public function testIsConditionAllowedNotShowErrorsWhenShowErrorsIsTrue()
    {
        $this->expectsDispatchWithErrors(['events' => ['aaa'], 'showErrors' => true]);

        $this->flashBag
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(['error', 'First error'], ['error', 'Second error']);

        $this->assertFalse($this->extendableCondition->isConditionAllowed([]));
    }

    public function testIsConditionAllowedNotShowErrorsWhenShowErrorsIsTrueAndMessageTypeIsInfo()
    {
        $this->expectsDispatchWithErrors(['events' => ['aaa'], 'showErrors' => true, 'messageType' => 'info']);

        $this->flashBag
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(['info', 'First error'], ['info', 'Second error']);

        $this->assertFalse($this->extendableCondition->isConditionAllowed([]));
    }

    public function testInitializeThrowsExceptionIfNoEventsSpecified()
    {
        $options = [];
        $this->expectException(ExtendableEventNameMissingException::class);
        $this->extendableCondition->initialize($options);
    }

    public function testInitializeNotThrowsException()
    {
        $options = ['events' => ['aaa', 'bbb']];
        $this->extendableCondition->initialize($options);
    }
}
