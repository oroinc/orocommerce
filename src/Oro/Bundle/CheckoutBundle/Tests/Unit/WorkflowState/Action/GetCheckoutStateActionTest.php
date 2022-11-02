<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Oro\Bundle\CheckoutBundle\WorkflowState\Action\GetCheckoutStateAction;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetCheckoutStateActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var CheckoutDiffStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $diffStorage;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var GetCheckoutStateAction */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->diffStorage = $this->createMock(CheckoutDiffStorageInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new GetCheckoutStateAction($this->contextAccessor, $this->diffStorage);
        $this->action->setDispatcher($this->dispatcher);
    }

    public function testExecute()
    {
        $entity = new \stdClass();
        $token = 'token';
        $attribute = new PropertyPath('token');

        $options = [
            'entity' => $entity,
            'token' => $token,
            'attribute' => $attribute,
        ];

        $state = ['saved state'];

        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->contextAccessor->expects($this->any())
            ->method('setValue')
            ->with([], $attribute, $state);

        $this->diffStorage->expects($this->once())
            ->method('getState')
            ->with($entity, $token)
            ->willReturn($state);

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    public function testInitializeRequiredField()
    {
        $options = [
            'entity' => new \stdClass(),
            'token' => 'token',
            'attribute' => new PropertyPath('token'),
        ];

        $this->action->initialize($options);
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
        $this->expectExceptionMessage('Parameter "token" is required');

        $options = [
            'entity' => new \stdClass(),

        ];

        $this->action->initialize($options);
    }

    public function testInitializeWithoutRequiredField()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "attribute" is required');

        $options = [
            'entity' => new \stdClass(),
            'token' => 'token',
        ];

        $this->action->initialize($options);
    }
}
