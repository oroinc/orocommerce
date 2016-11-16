<?php

namespace Oro\Tests\Unit\Component\Action\Condition;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\Action\Condition\ExtendableCondition;
use Oro\Component\Action\Exception\ExtendableEventNameMissingException;

class ExtendableConditionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ExtendableCondition
     */
    protected $extendableCondition;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->extendableCondition = new ExtendableCondition($this->eventDispatcher);
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

    public function testIsConditionAllowedIsFalseIfEventHasErrors()
    {
        $options = ['events' => ['aaa']];
        $this->extendableCondition->initialize($options);
        $this->eventDispatcher->expects($this->once())
            ->method('hasListeners')
            ->willReturn(true);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function($eventName, $event) {
                $event->addError('xxx');
            });

        $this->assertFalse($this->extendableCondition->isConditionAllowed([]));
    }

    public function testInitializeThrowsExceptionIfNoEventsSpecified()
    {
        $options = [];
        $this->setExpectedException(ExtendableEventNameMissingException::class);
        $this->extendableCondition->initialize($options);
    }

    public function testInitializeNotThrowsException()
    {
        $options = ['events' => ['aaa', 'bbb']];
        $this->extendableCondition->initialize($options);
    }
}
