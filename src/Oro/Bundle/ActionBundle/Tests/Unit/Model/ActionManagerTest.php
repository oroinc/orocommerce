<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionAssembler;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Model\AttributeAssembler;

use Oro\Bundle\ActionBundle\Model\FormOptionsAssembler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionFactory as FunctionFactory;

use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ActionConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FunctionFactory $functionFactory */
    protected $functionFactory;

    /** @var ConditionFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $conditionFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeAssembler */
    protected $attributeAssembler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormOptionsAssembler */
    protected $formOptionsAssembler;

    /** @var ActionAssembler */
    protected $assembler;

    /** @var ActionManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($class) {
                return $class;
            });

        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturn(true);

        $this->configurationProvider = $this
            ->getMockBuilder('Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->functionFactory = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\AttributeAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formOptionsAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\FormOptionsAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationProvider->expects($this->once())
            ->method('getActionConfiguration')
            ->willReturn($this->getConfiguration());

        $this->assembler = new ActionAssembler(
            $this->functionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler
        );

        $this->manager = new ActionManager($this->doctrineHelper, $this->configurationProvider, $this->assembler);
    }

    /**
     * @param array $context
     * @param array $expectedData
     *
     * @dataProvider getActionsProvider
     */
    public function testHasActions(array $context, array $expectedData)
    {
        $this->assertEquals($expectedData['hasActions'], $this->manager->hasActions($context));
    }

    /**
     * @param array $context
     * @param array $expectedData
     *
     * @dataProvider getActionsProvider
     */
    public function testGetActions(array $context, array $expectedData)
    {
        if (isset($context['entityClass'])) {
            if (isset($context['entityId'])) {
                $this->doctrineHelper->expects($this->any())
                    ->method('getEntityReference')
                    ->willReturnCallback(function ($className, $id) {
                        $obj = new \stdClass();
                        $obj->id = $id;

                        return $obj;
                    });
            } else {
                $this->doctrineHelper->expects($this->any())
                    ->method('createEntityInstance')
                    ->willReturn(new \stdClass());
            }
        }

        $this->assertGetActions($expectedData['actions'], $context);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getActionsAndMultipleCallsProvider
     */
    public function testGetActionsAndMultipleCalls(array $inputData, array $expectedData)
    {
        $this->assertGetActions($expectedData['actions1'], $inputData['context1']);
        $this->assertGetActions($expectedData['actions2'], $inputData['context2']);
        $this->assertGetActions($expectedData['actions3'], $inputData['context3']);
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $context
     * @param bool $throwEntityManagerException
     */
    public function testExecute(array $context, $throwEntityManagerException = false)
    {
        if (isset($context['entityClass'], $context['entityId'])) {
            $this->assertEntityManagerCalled('stdClass', $throwEntityManagerException);

            if ($throwEntityManagerException) {
                $this->setExpectedException('Exception');
            }
        }

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($className, $id) {
                $obj = new $className();
                $obj->id = $id;

                return $obj;
            });

        $action = $this->createActionMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionAssembler $assembler */
        $assembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionAssembler')
            ->disableOriginalConstructor()
            ->getMock();
        $assembler->expects($this->once())
            ->method('assemble')
            ->willReturn(['test_action' => $action]);

        $this->manager = new ActionManager($this->doctrineHelper, $this->configurationProvider, $assembler);

        $this->assertInstanceOf(
            'Oro\Bundle\ActionBundle\Model\ActionContext',
            $this->manager->execute($context, 'test_action')
        );
    }

    /**
     * @param string $className
     * @param bool $throwException
     */
    protected function assertEntityManagerCalled($className, $throwException = false)
    {
        $entityManager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $entityManager->expects($this->once())
            ->method('beginTransaction');

        if ($throwException) {
            $entityManager->expects($this->once())
                ->method('flush')
                ->willThrowException(new \Exception());
            $entityManager->expects($this->once())
                ->method('rollback');
        } else {
            $entityManager->expects($this->once())
                ->method('flush');
            $entityManager->expects($this->once())
                ->method('commit');
        }

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->isInstanceOf($className))
            ->willReturn($entityManager);
    }

    /**
     * @return Action|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createActionMock()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionDefinition $actionDefinition */
        $actionDefinition = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $actionDefinition->expects($this->once())
            ->method('getRoutes')
            ->willReturn(['route1']);
        $actionDefinition->expects($this->once())
            ->method('getEntities')
            ->willReturn(['stdClass']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Action $action */
        $action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->getMock();
        $action->expects($this->any())
            ->method('getDefinition')
            ->willReturn($actionDefinition);
        $action->expects($this->any())
            ->method('getName')
            ->willReturn('test_action');
        $action->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $action->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);
        $action->expects($this->once())
            ->method('execute');

        return $action;
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'route1' => [
                'context' => [
                    'route' => 'route1'
                ]
            ],
            'route1 without entity id' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'stdClass'
                ]
            ],
            'entity' => [
                'context' => [
                    'entityClass' => 'stdClass',
                    'entityId' => 1
                ]
            ],
            'route1 and entity' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'stdClass',
                    'entityId' => 1
                ]
            ],
            'route1 and entity and exception' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'stdClass',
                    'entityId' => 1
                ],
                'throwEntityManagerException' => true
            ],
        ];
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\ActionNotFoundException
     * @expectedExceptionMessage Action with name "test_action" not found
     */
    public function testExecuteException()
    {
        $this->manager->execute([], 'test_action');
    }

    /**
     * @param array $expectedActions
     * @param array $inputContext
     */
    protected function assertGetActions(array $expectedActions, array $inputContext)
    {
        $this->assertEquals($expectedActions, array_keys($this->manager->getActions($inputContext)));
    }

    /**
     * @return array
     */
    public function getActionsProvider()
    {
        return [
            'empty context' => [
                'context' => [],
                'expected' => [
                    'actions' => [],
                    'hasActions' => false,
                ],
            ],
            'incorrect context parameter' => [
                'context' => [
                    'entityId' => 1,
                ],
                'expected' => [
                    'actions' => [],
                    'hasActions' => false,
                ],
            ],
            'route1' => [
                'context' => [
                    'route' => 'route1',
                ],
                'expected' => [
                    'actions' => [
                        'action4',
                        'action2',
                    ],
                    'hasActions' => true,
                ],
            ],
            'entity1 without id' => [
                'context' => [
                    'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                ],
                'expected' => [
                    'actions' => [],
                    'hasActions' => false,
                ],
            ],
            'entity1' => [
                'context' => [
                    'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'entityId' => 1,
                ],
                'expected' => [
                    'actions' => [
                        'action4',
                        'action3',
                    ],
                    'hasActions' => true,
                ],
            ],
            'route1 & entity1' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'entityId' => 1,
                ],
                'expected' => [
                    'actions' => [
                        'action4',
                        'action3',
                        'action2',
                    ],
                    'hasActions' => true,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getActionsAndMultipleCallsProvider()
    {
        return [
            [
                'input' => [
                    'context1' => [],
                    'context2' => [
                        'route' => 'route1',
                    ],
                    'context3' => [
                        'route' => 'route2',
                        'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                        'entityId' => '2',
                    ],
                ],
                'expected' => [
                    'actions1' => [],
                    'actions2' => [
                        'action4',
                        'action2',
                    ],
                    'actions3' => [
                        'action4',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getConfiguration()
    {
        return [
            'action1' => [
                'label' => 'Label1',
                'routes' => [],
                'entities' => [],
                'order' => 50,
            ],
            'action2' => [
                'label' => 'Label2',
                'routes' => [
                    'route1',
                ],
                'entities' => [],
                'order' => 40,
            ],
            'action3' => [
                'label' => 'Label3',
                'routes' => [],
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                ],
                'order' => 30,
            ],
            'action4' => [
                'label' => 'Label4',
                'routes' => [
                    'route1',
                    'route2',
                ],
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                ],
                'order' => 20,
            ],
            'action5' => [
                'label' => 'Label5',
                'routes' => [
                    'route2',
                    'route3',
                ],
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3',
                ],
                'order' => 10,
                'enabled' => false,
            ],
        ];
    }
}
