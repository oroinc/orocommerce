<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Action\GenerateCheckoutStateSnapshotAction;

class GenerateCheckoutStateSnapshotActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextAccessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextAccessor;

    /** @var CheckoutStateDiffManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $diffManager;

    /** @var GenerateCheckoutStateSnapshotAction */
    protected $action;

    /**  @var EventDispatcherInterface */
    protected $dispatcher;

    protected function setUp()
    {

        $this->contextAccessor = $this->getMock(ContextAccessor::class);
        $this->diffManager = $this->getMockBuilder(CheckoutStateDiffManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new GenerateCheckoutStateSnapshotAction($this->contextAccessor, $this->diffManager);

        $this->dispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->action->setDispatcher($this->dispatcher);
    }

    protected function tearDown()
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

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Parameter "entity" is required
     */
    public function testInitializeWithoutRequiredFieldEntity()
    {
        $options = [];

        $this->action->initialize($options);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Parameter "attribute" is required
     */
    public function testInitializeWithoutRequiredFieldAttribute()
    {
        $options = [
            'entity' => new \stdClass(),
        ];

        $this->action->initialize($options);
    }
}
