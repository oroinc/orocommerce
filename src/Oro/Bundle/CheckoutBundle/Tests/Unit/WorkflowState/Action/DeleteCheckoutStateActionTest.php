<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Oro\Bundle\CheckoutBundle\WorkflowState\Action\DeleteCheckoutStateAction;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteCheckoutStateActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var CheckoutDiffStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $diffStorage;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var DeleteCheckoutStateAction */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->diffStorage = $this->createMock(CheckoutDiffStorageInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new DeleteCheckoutStateAction($this->contextAccessor, $this->diffStorage);
        $this->action->setDispatcher($this->dispatcher);
    }

    public function testExecute()
    {
        $entity = new \stdClass();
        $options = [
            'entity' => $entity,
        ];

        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->diffStorage->expects($this->once())
            ->method('deleteStates')
            ->with($entity, null);

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    public function testExecuteWithToken()
    {
        $entity = new \stdClass();
        $token = 'token';

        $options = [
            'entity' => $entity,
            'token' => $token,
        ];

        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->diffStorage->expects($this->once())
            ->method('deleteStates')
            ->with($entity, $token);

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    public function testInitializeRequiredField()
    {
        $options = [
            'entity' => new \stdClass(),
        ];

        $this->action->initialize($options);
    }

    public function testInitializeWithoutRequiredField()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "entity" is required');

        $options = [];

        $this->action->initialize($options);
    }
}
