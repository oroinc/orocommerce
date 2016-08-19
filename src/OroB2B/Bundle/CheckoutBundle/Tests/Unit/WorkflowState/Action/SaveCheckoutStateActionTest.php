<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Action\SaveCheckoutStateAction;

class SaveCheckoutStateActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextAccessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextAccessor;

    /** @var CheckoutDiffStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $diffStorage;

    /** @var SaveCheckoutStateAction */
    protected $action;

    /**  @var EventDispatcherInterface */
    protected $dispatcher;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMock(ContextAccessor::class);
        $this->diffStorage = $this->getMock(CheckoutDiffStorageInterface::class);
        $this->action = new SaveCheckoutStateAction($this->contextAccessor, $this->diffStorage);

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
        $state = ['state'];

        $options = [
            'entity' => $entity,
            'state' => $state,
        ];

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->diffStorage
            ->expects($this->once())
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

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->contextAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with([], $attribute, $generatedToken);

        $this->diffStorage
            ->expects($this->once())
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

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->diffStorage
            ->expects($this->once())
            ->method('addState')
            ->with($entity, $state, ['token' => $token]);

        $this->action->initialize($options);
        $this->action->execute([]);
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
     * @expectedExceptionMessage Parameter "state" is required
     */
    public function testInitializeWithoutRequiredFieldToken()
    {
        $options = [
            'entity' => new \stdClass(),

        ];

        $this->action->initialize($options);
    }
}
