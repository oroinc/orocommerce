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
use Oro\Bundle\WorkflowBundle\Model\Action\Configurable as ConfigurableAction;
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

    public function testExecute()
    {
        $functions = ['testFunction' => []];

        /* @var $function FunctionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $function = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $function->expects(static::once())
            ->method('execute')
            ->with($this->context);

        $this->definition->expects(static::once())
            ->method('getPostFunctions')
            ->willReturn($functions);

        $this->functionFactory->expects(static::once())
            ->method('create')
            ->with(ConfigurableAction::ALIAS, $functions)
            ->willReturn($function);

        $this->action->execute($this->context);
    }

    public function testExecuteWithoutPostFunctions()
    {
        $this->definition->expects(static::once())
            ->method('getPostFunctions')
            ->willReturn([]);

        $this->functionFactory->expects(static::never())
            ->method('create');

        $this->action->execute($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\ForbiddenActionException
     * @expectedExceptionMessage Action "test_action" is not allowed.
     */
    public function testExecuteException()
    {
        /* @var $condition ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject */
        $condition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();
        $condition->expects(static::any())
            ->method('evaluate')
            ->with($this->context)
            ->willReturn(false);

        $config = ['test' => []];

        $this->definition->expects(static::once())
            ->method('getName')
            ->willReturn('test_action');
        $this->definition->expects(static::once())
            ->method('getPreConditions')
            ->willReturn(['test' => []]);

        $this->conditionFactory->expects(static::once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $config)
            ->willReturn($condition);

        $this->action->execute($this->context);
    }

    public function testIsAllowedNoConditionsSection()
    {
        $this->conditionFactory->expects(static::never())
            ->method(static::anything());

        static::assertTrue($this->action->isAllowed($this->context));
    }

    public function testIsAllowedNoConditions()
    {
        $condition = null;

        $this->definition->expects(static::once())
            ->method('getConditions')
            ->willReturn($condition);

        $this->conditionFactory->expects(static::never())
            ->method(static::anything());

        static::assertTrue($this->action->isAllowed($this->context));
    }

    public function testIsAllowed()
    {
        $this->context['data'] = new \stdClass();
        $conditions = [
            ['test' => []],
        ];
        /* @var $condition ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject */
        $condition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();
        $condition->expects(static::any())
            ->method('evaluate')
            ->with($this->context)
            ->willReturn(false);

        $this->definition->expects(static::once())
            ->method('getConditions')
            ->willReturn($conditions);

        $this->conditionFactory->expects(static::once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $conditions)
            ->willReturn($condition);

        static::assertFalse($this->action->isAllowed($this->context));
    }

    public function testIsAvailable()
    {
        $this->context['data'] = new \stdClass();
        $functions = [
            ['testFunction' => []],
        ];

        $conditions = [
            ['test' => []],
        ];

        /* @var $function FunctionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $function = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $function->expects(static::once())
            ->method('execute')
            ->with($this->context);

        /* @var $condition ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject */
        $condition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();
        $condition->expects(static::once())
            ->method('evaluate')
            ->with($this->context)
            ->willReturn(false);

        $this->definition->expects(static::once())
            ->method('getPreFunctions')
            ->willReturn($functions);

        $this->definition->expects(static::once())
            ->method('getPreConditions')
            ->willReturn($conditions);

        $this->functionFactory->expects(static::once())
            ->method('create')
            ->with(ConfigurableAction::ALIAS, $functions)
            ->willReturn($function);

        $this->conditionFactory->expects(static::once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $conditions)
            ->willReturn($condition);

        static::assertFalse($this->action->isAvailable($this->context));
    }

    public function testGetDefinition()
    {
        static::assertInstanceOf('Oro\Bundle\ActionBundle\Model\ActionDefinition', $this->action->getDefinition());
    }

    public function testIsEnabled()
    {
        $this->definition->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->assertEquals(true, $this->action->isEnabled());
    }

    public function testGetName()
    {
        $this->definition->expects($this->once())
            ->method('getName')
            ->willReturn('test name');

        $this->assertEquals('test name', $this->action->getName());
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
