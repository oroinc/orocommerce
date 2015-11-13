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

        static::assertTrue($this->action->isApplicable($this->context));
        static::assertTrue($this->action->isAllowed($this->context));
    }

    public function testIsApplicableIsAllowedNoConditions()
    {
        $condition = null;

        $this->definition->expects(static::once())
            ->method('getConditions')
            ->willReturn($condition);

        $this->conditionFactory->expects(static::never())
            ->method(static::anything());

        static::assertTrue($this->action->isApplicable($this->context));
        static::assertTrue($this->action->isAllowed($this->context));
    }

    public function testIsApplicable()
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
            ->with($this->context->getEntity())
            ->willReturn(false);

        $this->definition->expects(static::once())
            ->method('getConditions')
            ->willReturn($conditions);

        $this->conditionFactory->expects(static::once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $conditions[0])
            ->willReturn($condition);

        static::assertFalse($this->action->isApplicable($this->context));
    }

    public function testIsAllowed()
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
            ->with($this->context->getEntity())
            ->willReturn(false);

        $this->definition->expects(static::once())
            ->method('getPreConditions')
            ->willReturn($conditions);

        $this->conditionFactory->expects(static::once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $conditions[0])
            ->willReturn($condition);

        static::assertFalse($this->action->isAllowed($this->context));
    }

    public function testGetDefinition()
    {
        static::assertInstanceOf('Oro\Bundle\ActionBundle\Model\ActionDefinition', $this->action->getDefinition());
    }
}
