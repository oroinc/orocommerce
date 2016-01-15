<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Action\RunAction;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Action\CallServiceMethod;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Tests\Unit\Action\Stub\TestService;

use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class RunActionTest extends \PHPUnit_Framework_TestCase
{
    const ACTION_NAME = 'test_action';

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var RunAction */
    protected $action;

    /** @var ActionManager */
    protected $manager;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper $contextHelper */
        $contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $contextHelper->expects($this->any())
            ->method('getActionData')
            ->willReturn(new ActionData(['data' => ['param']]));

        $this->manager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new RunAction(new ContextAccessor(), $this->manager, $contextHelper);
        $this->action->setDispatcher($this->eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->action, $this->eventDispatcher, $this->container);
    }

    public function testInitialize()
    {
        $options = [
            'name' => 'test_action',
            'entity_class' => 'testClass',
            'entity_id' => 1,
            'attribute' => 'test'
        ];

        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface',
            $this->action->initialize($options)
        );

        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     *
     * @param array $inputData
     * @param string $exception
     * @param string $exceptionMessage
     * @param bool $hasService
     */
    public function testInitializeException(array $inputData, $exception, $exceptionMessage, $hasService = true)
    {
        $this->container->expects($this->any())
            ->method('has')
            ->willReturn($hasService);
        $this->container->expects($this->any())
            ->method('get')
            ->willReturn(new TestService());

        $this->setExpectedException($exception, $exceptionMessage);

        $this->action->initialize($inputData);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return [
            [
                'inputData' => [],
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Action name parameter is required'
            ],
            [
                'inputData' => [
                    'action' => 'test_action'
                ],
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Entity class parameter is required',
                'hasService' => false
            ],
            [
                'inputData' => [
                    'action' => 'test_action',
                    'entity_class' => 'entityClass'
                ],
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Entity id parameter is required',
            ]
        ];
    }

    public function testExecuteMethod()
    {
        $this->container->expects($this->exactly(1))
            ->method('execute')
            ->with('test_action')
            ->willReturn(true);

        $data = new ActionData(['param' => 'value']);
        $options = [
            'action' => 'test_action',
            'entity_class' => 'entityClass',
            'entity_id' => '1',
        ];

        $this->action->initialize($options);
        $this->action->execute($data);

        $this->assertEquals(
            ['param' => 'value', 'test' => TestService::TEST_METHOD_RESULT . 'value'],
            $data->getValues()
        );
    }

    public function testExecuteWithoutAttribute()
    {
        $this->assertContainerCalled('test_service');

        $data = new ActionData(['param' => 'value']);
        $options = array(
            'service' => 'test_service',
            'method' => 'testMethod',
            'method_parameters' => ['test']
        );

        $this->action->initialize($options);
        $this->action->execute($data);

        $this->assertEquals(['param' => 'value'], $data->getValues());
    }

    /**
     * @param string $serviceName
     * @param int $hasCalls
     * @param int $getCalls
     */
    protected function assertContainerCalled($serviceName, $hasCalls = 1, $getCalls = 2)
    {
        $this->container->expects($this->exactly($hasCalls))
            ->method('has')
            ->with($serviceName)
            ->willReturn(true);
        $this->container->expects($this->exactly($getCalls))
            ->method('get')
            ->with($serviceName)
            ->willReturn(new TestService());
    }
}
