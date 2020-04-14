<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Oro\Bundle\CheckoutBundle\WorkflowState\Action\DeleteCheckoutStateAction;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteCheckoutStateActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextAccessor;

    /** @var CheckoutDiffStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $diffStorage;

    /** @var DeleteCheckoutStateAction */
    protected $action;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->diffStorage = $this->createMock(CheckoutDiffStorageInterface::class);
        $this->action = new DeleteCheckoutStateAction($this->contextAccessor, $this->diffStorage);

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->action->setDispatcher($this->dispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->contextAccessor, $this->diffStorage, $this->dispatcher, $this->action);
    }

    public function testExecute()
    {
        $entity = new \stdClass();
        $options = [
            'entity' => $entity,
        ];

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->diffStorage
            ->expects($this->once())
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

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->diffStorage
            ->expects($this->once())
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
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "entity" is required');

        $options = [];

        $this->action->initialize($options);
    }
}
