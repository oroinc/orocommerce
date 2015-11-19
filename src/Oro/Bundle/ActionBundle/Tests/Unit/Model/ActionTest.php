<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionContext;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\WorkflowBundle\Model\Condition\Configurable as ConfigurableCondition;

use Oro\Component\ConfigExpression\ExpressionFactory;

class ActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionDefinition $definition */
    protected $definition;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory $conditionFactory */
    protected $conditionFactory;

    /** @var Action */
    protected $action;

    /** @var ActionContext */
    protected $context;

    protected function setUp()
    {
        $this->definition = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new Action($this->conditionFactory, $this->definition);
        $this->context = new ActionContext();
    }

    public function testInit()
    {
        // ToDo: implement
    }

    public function testExecute()
    {
        // ToDo: implement
    }

    public function testIsApplicableIsAllowedNoConditionsSection()
    {
        $this->conditionFactory->expects(static::never())
            ->method(static::anything());

        static::assertTrue($this->action->isPreConditionAllowed($this->context));
        static::assertTrue($this->action->isConditionAllowed($this->context));
    }

    public function testIsApplicableIsAllowedNoConditions()
    {
        $condition = null;

        $this->definition->expects(static::once())
            ->method('getConditions')
            ->willReturn($condition);

        $this->conditionFactory->expects(static::never())
            ->method(static::anything());

        static::assertTrue($this->action->isPreConditionAllowed($this->context));
        static::assertTrue($this->action->isConditionAllowed($this->context));
    }

    public function testIsConditionAllowed()
    {
        $this->context['data'] = new \stdClass();
        $conditions = [
            ['test' => []],
        ];
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

        static::assertFalse($this->action->isConditionAllowed($this->context));
    }

    public function testIsPreConditionAllowed()
    {
        $this->context['data'] = new \stdClass();
        $conditions = [
            ['test' => []],
        ];
        $condition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();
        $condition->expects(static::any())
            ->method('evaluate')
            ->with($this->context)
            ->willReturn(false);

        $this->definition->expects(static::once())
            ->method('getPreConditions')
            ->willReturn($conditions);

        $this->conditionFactory->expects(static::once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $conditions)
            ->willReturn($condition);

        static::assertFalse($this->action->isPreConditionAllowed($this->context));
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
}
