<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionContext;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\ActionBundle\Model\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\FormOptionsAssembler;

use Oro\Bundle\WorkflowBundle\Model\Action\ActionFactory as FunctionFactory;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface as FunctionInterface;
use Oro\Bundle\WorkflowBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\Condition\Configurable as ConfigurableCondition;

use Oro\Component\ConfigExpression\ExpressionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionDefinition */
    protected $definition;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FunctionFactory */
    protected $functionFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory */
    protected $conditionFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeAssembler */
    protected $attributeAssembler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormOptionsAssembler */
    protected $formOptionsAssembler;

    /** @var Action */
    protected $action;

    /** @var ActionContext */
    protected $context;

    protected function setUp()
    {
        $this->definition = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionDefinition')
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

        $this->action = new Action(
            $this->functionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler,
            $this->definition
        );

        $this->context = new ActionContext();
    }

    public function testGetName()
    {
        $this->definition->expects($this->once())
            ->method('getName')
            ->willReturn('test name');

        $this->assertEquals('test name', $this->action->getName());
    }

    public function testIsEnabled()
    {
        $this->definition->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->assertEquals(true, $this->action->isEnabled());
    }

    public function testGetDefinition()
    {
        $this->assertInstanceOf('Oro\Bundle\ActionBundle\Model\ActionDefinition', $this->action->getDefinition());
    }

    public function testInit()
    {
        $config = [
            ['initfunctions',  ['initfunctions']],
        ];

        $functions = [
            'initfunctions' => $this->createFunction($this->once(), $this->context),
        ];

        $this->definition->expects($this->any())
            ->method('getFunctions')
            ->will($this->returnValueMap($config));

        $this->functionFactory->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($type, $config) use ($functions) {
                return $functions[$config[0]];
            });

        $this->action->init($this->context);
    }

    /**
     * @param ActionContext $context
     * @param array $config
     * @param array $functions
     * @param array $conditions
     * @param string $actionName
     * @param string $exceptionMessage
     *
     * @dataProvider executeProvider
     */
    public function testExecute(
        ActionContext $context,
        array $config,
        array $functions,
        array $conditions,
        $actionName,
        $exceptionMessage = ''
    ) {
        $this->definition->expects($this->any())
            ->method('getName')
            ->willReturn($actionName);

        $this->definition->expects($this->any())
            ->method('getFunctions')
            ->will($this->returnValueMap($config));

        $this->definition->expects($this->any())
            ->method('getConditions')
            ->will($this->returnValueMap($config));

        $this->functionFactory->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($type, $config) use ($functions) {
                return $functions[$config[0]];
            });

        $this->conditionFactory->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($type, $config) use ($conditions) {
                return $conditions[$config[0]];
            });

        $errors = new ArrayCollection();

        if ($exceptionMessage) {
            $this->setExpectedException(
                'Oro\Bundle\ActionBundle\Exception\ForbiddenActionException',
                $exceptionMessage
            );
        }

        $this->action->execute($context, $errors);

        $this->assertEmpty($errors->toArray());
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider isAvailableProvider
     */
    public function testIsAvailable(array $inputData, array $expectedData)
    {
        $this->definition->expects($this->any())
            ->method('getConditions')
            ->will($this->returnValueMap($inputData['config']['conditions']));

        $this->definition->expects($this->any())
            ->method('getFormOptions')
            ->willReturn($inputData['config']['form_options']);

        $this->conditionFactory->expects($expectedData['conditionFactory'])
            ->method('create')
            ->willReturnCallback(function ($type, $config) use ($inputData) {
                return $inputData['conditions'][$config[0]];
            });

        $this->assertEquals($expectedData['available'], $this->action->isAvailable($inputData['context']));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function isAvailableProvider()
    {
        $context = new ActionContext();

        return [
            'no conditions' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [],
                        'form_options' => [],
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->never(),
                    'available' => true,
                    'errors' => [],
                ],
            ],
            '!isPreConditionAllowed' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                        'form_options' => [],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $context, false),
                        'conditions' => $this->createCondition($this->never(), $context, true),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(1),
                    'available' => false,
                ],
            ],
            '!isConditionAllowed' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                        'form_options' => [],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $context, true),
                        'conditions' => $this->createCondition($this->once(), $context, false),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(2),
                    'available' => false,
                    'errors' => ['error3', 'error4'],
                ],
            ],
            'allowed' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                        'form_options' => [],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $context, true),
                        'conditions' => $this->createCondition($this->once(), $context, true),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(2),
                    'available' => true,
                    'errors' => [],
                ],
            ],
            'hasForm and no conditions' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [],
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute1' => [],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->never(),
                    'available' => true,
                    'errors' => [],
                ],
            ],
            'hasForm and !isPreConditionAllowed' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute2' => [],
                            ],
                        ],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $context, false),
                        'conditions' => $this->createCondition($this->never(), $context, true),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(1),
                    'available' => false,
                ],
            ],
            'hasForm and allowed' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute3' => [],
                            ],
                        ],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $context, true),
                        'conditions' => $this->createCondition($this->never(), $context, true),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(1),
                    'available' => true,
                    'errors' => [],
                ],
            ],
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider isAllowedProvider
     */
    public function testIsAllowed(array $inputData, array $expectedData)
    {
        $this->definition->expects($this->any())
            ->method('getConditions')
            ->will($this->returnValueMap($inputData['config']['conditions']));

        $this->conditionFactory->expects($expectedData['conditionFactory'])
            ->method('create')
            ->willReturnCallback(function ($type, $config) use ($inputData) {
                return $inputData['conditions'][$config[0]];
            });

        $this->assertEquals($expectedData['allowed'], $this->action->isAllowed($inputData['context']));
    }

    /**
     * @param array $input
     * @param bool $expected
     *
     * @dataProvider hasFormProvider
     */
    public function testHasForm(array $input, $expected)
    {
        $this->definition->expects($this->once())
            ->method('getFormOptions')
            ->willReturn($input);
        $this->assertEquals($expected, $this->action->hasForm());
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        $context = new ActionContext();

        $config = [
            ['prefunctions', ['prefunctions']],
            ['postfunctions', ['postfunctions']],
            ['preconditions', ['preconditions']],
            ['conditions', ['conditions']],
        ];

        return [
            '!isPreConditionAllowed' => [
                'context' => $context,
                'config' => $config,
                'functions' => [
                    'prefunctions' => $this->createFunction($this->once(), $context),
                    'postfunctions' => $this->createFunction($this->never(), $context),
                ],
                'conditions' => [
                    'preconditions' => $this->createCondition($this->once(), $context, false),
                    'conditions' => $this->createCondition($this->never(), $context, true),
                ],
                'actionName' => 'TestName1',
                'exception' => 'Action "TestName1" is not allowed.'
            ],
            '!isConditionAllowed' => [
                'context' => $context,
                'config' => $config,
                'functions' => [
                    'prefunctions' => $this->createFunction($this->once(), $context),
                    'postfunctions' => $this->createFunction($this->never(), $context),
                ],
                'conditions' => [
                    'preconditions' => $this->createCondition($this->once(), $context, true),
                    'conditions' => $this->createCondition($this->once(), $context, false),
                ],
                'actionName' => 'TestName2',
                'exception' => 'Action "TestName2" is not allowed.'
            ],
            'isAllowed' => [
                'context' => $context,
                'config' => $config,
                'functions' => [
                    'prefunctions' => $this->createFunction($this->once(), $context),
                    'postfunctions' => $this->createFunction($this->once(), $context),
                ],
                'conditions' => [
                    'preconditions' => $this->createCondition($this->once(), $context, true),
                    'conditions' => $this->createCondition($this->once(), $context, true),
                ],
                'actionName' => 'TestName3',
            ],
        ];
    }

    /**
     * @return array
     */
    public function isAllowedProvider()
    {
        $context = new ActionContext();

        return [
            'no conditions' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [],
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->never(),
                    'allowed' => true,
                    'errors' => [],
                ],
            ],
            '!isPreConditionAllowed' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $context, false),
                        'conditions' => $this->createCondition($this->never(), $context, true),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(1),
                    'allowed' => false,
                ],
            ],
            '!isConditionAllowed' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $context, true),
                        'conditions' => $this->createCondition($this->once(), $context, false),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(2),
                    'allowed' => false,
                    'errors' => ['error3', 'error4'],
                ],
            ],
            'allowed' => [
                'input' => [
                    'context' => $context,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $context, true),
                        'conditions' => $this->createCondition($this->once(), $context, true),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(2),
                    'allowed' => true,
                    'errors' => [],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function hasFormProvider()
    {
        return [
            'empty' => [
                'input' => [],
                'expected' => false,
            ],
            'empty attribute_fields' => [
                'input' => ['attribute_fields' => []],
                'expected' => false,
            ],
            'filled' => [
                'input' => ['attribute_fields' => ['attribute' => []]],
                'expected' => true,
            ],
        ];
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects
     * @param ActionContext $context
     * @return FunctionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createFunction(
        \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects,
        ActionContext $context
    ) {
        /* @var $function FunctionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $function = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $function->expects($expects)
            ->method('execute')
            ->with($context);

        return $function;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects
     * @param ActionContext $context
     * @param bool $returnValue
     * @return ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createCondition(
        \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects,
        ActionContext $context,
        $returnValue
    ) {
        /* @var $condition ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject */
        $condition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();

        $condition->expects($expects)
            ->method('evaluate')
            ->with($context)
            ->willReturn($returnValue);

        return $condition;
    }

    public function testGetAttributeManager()
    {
        $attributes = ['attribute' => ['label' => 'attr_label']];

        $this->definition->expects($this->once())
            ->method('getAttributes')
            ->willReturn($attributes);

        $this->context['data'] = new \stdClass();

        $attribute = new Attribute();
        $attribute->setName('test_attr');

        $this->attributeAssembler->expects($this->once())
            ->method('assemble')
            ->with($this->context, $attributes)
            ->willReturn(new ArrayCollection([$attribute]));

        $attributeManager = $this->action->getAttributeManager($this->context);

        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\AttributeManager', $attributeManager);
        $this->assertEquals(new ArrayCollection(['test_attr' => $attribute]), $attributeManager->getAttributes());
    }
}
