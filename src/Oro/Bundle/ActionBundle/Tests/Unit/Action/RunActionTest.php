<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ActionBundle\Action\RunAction;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class RunActionTest extends \PHPUnit_Framework_TestCase
{
    const ACTION_NAME = 'test_action';

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var RunAction */
    protected $function;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionManager */
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

        $this->function = new RunAction(new ContextAccessor(), $this->manager, $contextHelper);
        $this->function->setDispatcher($this->eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->function, $this->eventDispatcher, $this->container);
    }

    public function testInitialize()
    {
        $options = [
            'action' => self::ACTION_NAME,
            'entity_class' => 'testClass',
            'entity_id' => 1,
        ];

        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface',
            $this->function->initialize($options)
        );

        $this->assertAttributeEquals($options, 'options', $this->function);
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
        $this->setExpectedException($exception, $exceptionMessage);

        $this->function->initialize($inputData);
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
                    'action' => self::ACTION_NAME
                ],
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Entity class parameter is required',
                'hasService' => false
            ],
            [
                'inputData' => [
                    'action' => self::ACTION_NAME,
                    'entity_class' => 'entityClass'
                ],
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Entity id parameter is required',
            ]
        ];
    }

    public function testExecuteMethod()
    {
        $this->assertManagerCalled();

        $data = new ActionData(['param' => 'value']);
        $options = [
            'action' => self::ACTION_NAME,
            'entity_class' => 'entityClass',
            'entity_id' => '1',
        ];

        $this->function->initialize($options);
        $this->function->execute($data);

        $this->assertEquals(
            ['param' => 'value'],
            $data->getValues()
        );
    }

    public function testExecuteWithoutAttribute()
    {
        $data = new ActionData(['param' => 'value']);
        $this->assertManagerCalled();

        $options = array(
            'action' => self::ACTION_NAME,
            'entity_class' => 'testClass',
            'entity_id' => 1,
        );

        $this->function->initialize($options);
        $this->function->execute($data);

        $this->assertEquals(['param' => 'value'], $data->getValues());
    }

    /**
     * @param int $executeCalls
     */
    protected function assertManagerCalled($executeCalls = 1)
    {
        $this->manager->expects($this->exactly($executeCalls))
            ->method('execute')
            ->withAnyParameters()
            ->willReturn(true);
    }
}
