<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Oro\Bundle\CheckoutBundle\WorkflowState\Action\GenerateCheckoutStateSnapshotAction;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class GenerateCheckoutStateSnapshotActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var CheckoutStateDiffManager|\PHPUnit\Framework\MockObject\MockObject */
    private $diffManager;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var GenerateCheckoutStateSnapshotAction */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->diffManager = $this->createMock(CheckoutStateDiffManager::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new GenerateCheckoutStateSnapshotAction($this->contextAccessor, $this->diffManager);
        $this->action->setDispatcher($this->dispatcher);
    }

    public function testExecute()
    {
        $entity = new \stdClass();
        $attributePath = new PropertyPath('attribute');

        $options = [
            'entity' => $entity,
            'attribute' => $attributePath,
        ];

        $state = ['generated_state'];

        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with([], $attributePath, $state);

        $this->diffManager->expects($this->once())
            ->method('getCurrentState')
            ->with($entity)
            ->willReturn($state);

        $this->action->initialize($options);
        $this->action->execute([]);
    }

    public function testInitializeRequiredField()
    {
        $options = [
            'entity' => new \stdClass(),
            'attribute' => new PropertyPath('attribute'),
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

    public function testInitializeWithoutRequiredFieldAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "attribute" is required');

        $options = [
            'entity' => new \stdClass(),
        ];

        $this->action->initialize($options);
    }
}
