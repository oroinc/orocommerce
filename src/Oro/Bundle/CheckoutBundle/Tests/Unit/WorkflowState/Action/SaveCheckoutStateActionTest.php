<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Oro\Bundle\CheckoutBundle\WorkflowState\Action\SaveCheckoutStateAction;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class SaveCheckoutStateActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var CheckoutDiffStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $diffStorage;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var SaveCheckoutStateAction */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->diffStorage = $this->createMock(CheckoutDiffStorageInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new SaveCheckoutStateAction($this->contextAccessor, $this->diffStorage);
        $this->action->setDispatcher($this->dispatcher);
    }

    public function testExecute()
    {
        $entity = new \stdClass();
        $state = ['state'];

        $options = [
            'entity' => $entity,
            'state' => $state,
        ];

        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->diffStorage->expects($this->once())
            ->method('addState')
            ->with($entity, $state, []);

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    public function testExecuteWithAttribute()
    {
        $entity = new \stdClass();
        $state = ['state'];
        $attribute = new PropertyPath('attribute');

        $options = [
            'entity' => $entity,
            'state' => $state,
            'attribute' => $attribute,
        ];

        $generatedToken = 'generated_token';

        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with([], $attribute, $generatedToken);

        $this->diffStorage->expects($this->once())
            ->method('addState')
            ->with($entity, $state, [])
            ->willReturn($generatedToken);

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    public function testExecuteWithToken()
    {
        $entity = new \stdClass();
        $state = ['state'];
        $token = 'token';

        $options = [
            'entity' => $entity,
            'state' => $state,
            'token' => $token,
        ];

        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->diffStorage->expects($this->once())
            ->method('addState')
            ->with($entity, $state, ['token' => $token]);

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    public function testInitializeWithoutRequiredFieldEntity()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "entity" is required');

        $options = [];

        $this->action->initialize($options);
    }

    public function testInitializeWithoutRequiredFieldToken()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "state" is required');

        $options = [
            'entity' => new \stdClass(),
        ];

        $this->action->initialize($options);
    }
}
