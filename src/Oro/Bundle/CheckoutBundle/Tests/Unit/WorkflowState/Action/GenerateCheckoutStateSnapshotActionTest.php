<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Oro\Bundle\CheckoutBundle\WorkflowState\Action\GenerateCheckoutStateSnapshotAction;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class GenerateCheckoutStateSnapshotActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextAccessor;

    /** @var CheckoutStateDiffManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $diffManager;

    /** @var GenerateCheckoutStateSnapshotAction */
    protected $action;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->diffManager = $this->getMockBuilder(CheckoutStateDiffManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new GenerateCheckoutStateSnapshotAction($this->contextAccessor, $this->diffManager);

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
        $attributePath = new PropertyPath('attribute');

        $options = [
            'entity' => $entity,
            'attribute' => $attributePath,
        ];

        $state = ['generated_state'];

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->contextAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with([], $attributePath, $state);

        $this->diffManager
            ->expects($this->once())
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
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "entity" is required');

        $options = [];

        $this->action->initialize($options);
    }

    public function testInitializeWithoutRequiredFieldAttribute()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage('Parameter "attribute" is required');

        $options = [
            'entity' => new \stdClass(),
        ];

        $this->action->initialize($options);
    }
}
