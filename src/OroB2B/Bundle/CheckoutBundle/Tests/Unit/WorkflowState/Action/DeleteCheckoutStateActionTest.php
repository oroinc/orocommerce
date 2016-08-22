<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Action\DeleteCheckoutStateAction;

class DeleteCheckoutStateActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextAccessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextAccessor;

    /** @var CheckoutDiffStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $diffStorage;

    /** @var DeleteCheckoutStateAction */
    protected $action;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMock(ContextAccessor::class);
        $this->diffStorage = $this->getMock(CheckoutDiffStorageInterface::class);
        $this->action = new DeleteCheckoutStateAction($this->contextAccessor, $this->diffStorage);

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

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Parameter "entity" is required
     */
    public function testInitializeWithoutRequiredField()
    {
        $options = [];

        $this->action->initialize($options);
    }
}
