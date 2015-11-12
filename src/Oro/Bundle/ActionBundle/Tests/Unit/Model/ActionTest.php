<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionContext;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\WorkflowBundle\Model\Condition\Configurable as ConfigurableCondition;

use Oro\Component\ConfigExpression\ExpressionFactory;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

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

        static::assertTrue($this->action->isApplicable($this->context));
        static::assertTrue($this->action->isAllowed($this->context));
    }

    public function testIsApplicableIsAllowedNoConditions()
    {
        $conditionConfiguration = null;

        $this->definition->expects(static::once())
            ->method('getConditionsConfiguration')
            ->willReturn($conditionConfiguration);

        $this->conditionFactory->expects(static::never())
            ->method(static::anything());

        static::assertTrue($this->action->isApplicable($this->context));
        static::assertTrue($this->action->isAllowed($this->context));
    }

    public function testIsApplicable()
    {
        $this->context['data'] = new \stdClass();
        $conditionsConfiguration = [
            ['test' => []],
        ];
        $condition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();
        $condition->expects(static::any())
            ->method('evaluate')
            ->with($this->context->getEntity())
            ->willReturn(false);

        $this->definition->expects(static::once())
            ->method('getConditionsConfiguration')
            ->willReturn($conditionsConfiguration);

        $this->conditionFactory->expects(static::once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $conditionsConfiguration[0])
            ->willReturn($condition);

        static::assertFalse($this->action->isApplicable($this->context));
    }

    public function testIsAllowed()
    {
        $this->context['data'] = new \stdClass();
        $conditionsConfiguration = [
            ['test' => []],
        ];
        $condition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();
        $condition->expects(static::any())
            ->method('evaluate')
            ->with($this->context->getEntity())
            ->willReturn(false);

        $this->definition->expects(static::once())
            ->method('getPreConditionsConfiguration')
            ->willReturn($conditionsConfiguration);

        $this->conditionFactory->expects(static::once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $conditionsConfiguration[0])
            ->willReturn($condition);

        static::assertFalse($this->action->isAllowed($this->context));
    }

    public function testGetSetDefinition()
    {
        $definition = new ActionDefinition();
        $definition->setName('name2');
        static::assertNotEquals($definition, $this->action->getDefinition());
        $this->action->setDefinition($definition);
        static::assertEquals($definition, $this->action->getDefinition());
    }
}
