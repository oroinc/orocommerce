<?php

namespace Oro\Tests\Unit\Component\Action\Condition;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var ExtendableCondition
     */
    protected $extendableCondition;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->extendableCondition = new ExtendableCondition(
            $this->eventDispatcher,
            $this->flashBag,
            $this->translator
        );
        $this->extendableCondition->setContextAccessor(new ContextAccessor());
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

    public function testIsConditionAllowedIsFalseIfEventHasErrorsWithDefaultOptions()
    {
        $this->expectsDispatchWithErrors(['events' => ['aaa']]);

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans');
        $this->flashBag
            ->expects($this->never())
            ->method('add');

        $this->assertFalse($this->extendableCondition->isConditionAllowed(new ActionData([])));
    }

    public function testIsConditionAllowedNotShowErrorsWhenShowErrorsIsTrue()
    {
        $this->expectsDispatchWithErrors(['events' => ['aaa'], 'showErrors' => true]);

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(['First error'], ['Second error'])
            ->willReturnOnConsecutiveCalls('Translated first error', 'Translated second error');
        $this->flashBag
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(['error', 'Translated first error'], ['error', 'Translated second error']);

        $this->assertFalse($this->extendableCondition->isConditionAllowed([]));
    }

    public function testIsConditionAllowedNotShowErrorsWhenShowErrorsIsTrueAndMessageTypeIsInfo()
    {
        $this->expectsDispatchWithErrors(['events' => ['aaa'], 'showErrors' => true, 'messageType' => 'info']);

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(['First error'], ['Second error'])
            ->willReturnOnConsecutiveCalls('Translated first error', 'Translated second error');
        $this->flashBag
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(['info', 'Translated first error'], ['info', 'Translated second error']);

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
