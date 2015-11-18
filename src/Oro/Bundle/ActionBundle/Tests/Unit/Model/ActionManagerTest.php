<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\ActionAssembler;
use Oro\Bundle\ActionBundle\Model\ActionManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ActionConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var ConditionFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $conditionFactory;

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

        $this->conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationProvider->expects($this->once())
            ->method('getActionConfiguration')
            ->willReturn($this->getConfiguration());

        $this->assembler = new ActionAssembler($this->conditionFactory);

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
                    ->willReturnCallback(function ($className) {
                        return new \stdClass();
                    });
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
