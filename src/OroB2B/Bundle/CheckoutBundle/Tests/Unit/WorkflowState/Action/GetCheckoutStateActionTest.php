<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Action\GetCheckoutStateAction;

class GetCheckoutStateActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextAccessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextAccessor;

    /** @var CheckoutDiffStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $diffStorage;

    /** @var GetCheckoutStateAction */
    protected $action;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMock(ContextAccessor::class);
        $this->diffStorage = $this->getMock(CheckoutDiffStorageInterface::class);
        $this->action = new GetCheckoutStateAction($this->contextAccessor, $this->diffStorage);

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
        $token = 'token';
        $attribute = new PropertyPath('token');

        $options = [
            'entity' => $entity,
            'token' => $token,
            'attribute' => $attribute,
        ];

        $state = ['saved state'];

        $this->contextAccessor
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->contextAccessor
            ->expects($this->any())
            ->method('setValue')
            ->with([], $attribute, $state);

        $this->diffStorage
            ->expects($this->once())
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
     * @expectedExceptionMessage Parameter "token" is required
     */
    public function testInitializeWithoutRequiredFieldToken()
    {
        $options = [
            'entity' => new \stdClass(),

        ];

        $this->action->initialize($options);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Parameter "attribute" is required
     */
    public function testInitializeWithoutRequiredField()
    {
        $options = [
            'entity' => new \stdClass(),
            'token' => 'token',
        ];

        $this->action->initialize($options);
    }
}
