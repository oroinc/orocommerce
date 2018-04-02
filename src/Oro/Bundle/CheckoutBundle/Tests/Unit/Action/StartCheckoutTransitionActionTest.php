<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Oro\Bundle\CheckoutBundle\Action\StartCheckoutTransitionAction;
use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StartCheckoutTransitionActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StartCheckoutTransitionAction
     */
    private $action;

    /**
     * @var IsWorkflowStartFromShoppingListAllowed|\PHPUnit_Framework_MockObject_MockObject
     */
    private $isWorkflowStartFromShoppingListAllowed;

    /**
     * @var ContextAccessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextAccessor;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->isWorkflowStartFromShoppingListAllowed =
            $this->createMock(IsWorkflowStartFromShoppingListAllowed::class);
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->action = new StartCheckoutTransitionAction(
            $this->contextAccessor,
            $this->isWorkflowStartFromShoppingListAllowed
        );
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher->expects($this->any())->method('dispatch');
        $this->action->setDispatcher($this->eventDispatcher);
        $this->action->initialize([StartCheckoutTransitionAction::OPTION_KEY_ATTRIBUTE => 'some_name']);
    }

    public function testExecuteActionForGuest()
    {
        $this->isWorkflowStartFromShoppingListAllowed
            ->expects($this->once())
            ->method('isAllowedForGuest')
            ->willReturn(true);

        $context = [];
        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with($context, 'some_name', StartCheckoutTransitionAction::TRANSITION_FOR_GUEST);
        $this->action->execute($context);
    }

    public function testExecuteActionForNotGuest()
    {
        $this->isWorkflowStartFromShoppingListAllowed
            ->expects($this->once())
            ->method('isAllowedForGuest')
            ->willReturn(false);

        $context = [];
        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with($context, 'some_name', '');
        $this->action->execute($context);
    }

    /**
     * @expectedException  \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute name parameter is required
     */
    public function testInitializeException()
    {
        $this->action->initialize([]);
    }
}
