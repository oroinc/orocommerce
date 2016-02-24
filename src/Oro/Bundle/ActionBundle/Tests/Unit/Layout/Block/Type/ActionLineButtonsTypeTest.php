<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\ActionBundle\Layout\Block\Type\ActionButtonType;
use Oro\Component\Layout\LayoutManipulatorInterface;

class ActionLineButtonsTypeTest extends AbstractActionButtonsTypeTest
{
    /**
     * @dataProvider buildBlockDataProvider
     * @param string|null $groupValue
     * @param string $executionRoute
     * @param string $dialogRoute
     * @param boolean $actionHasForm
     * @param array $expectedOptions
     */
    public function testBuildBlock($groupValue, $executionRoute, $dialogRoute, $actionHasForm, array $expectedOptions)
    {
        $actionName = 'action1';
        $options = $this->getOptions($groupValue, $executionRoute, $dialogRoute, $actionHasForm, $actionName);
        $builderId = 'builder';
        /** @var LayoutManipulatorInterface|\PHPUnit_Framework_MockObject_MockObject $manipulator */
        $manipulator = $this->getMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $manipulator->expects($this->once())->method('add')->with(
            $actionName . '_button',
            $builderId,
            ActionButtonType::NAME,
            $expectedOptions
        );
        $blockBuilder = $this->getBlockBuilder($manipulator, $builderId);

        $this->blockType->buildBlock($blockBuilder, $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTypeClassName()
    {
        return 'Oro\Bundle\ActionBundle\Layout\Block\Type\ActionLineButtonsType';
    }

    /**
     * {@inheritDoc}
     */
    protected function setResolverExpectations($resolver)
    {
        $resolver->expects($this->at(3))->method('setOptional')->with(['exclude_action', 'suffix', 'hide_icons']);
    }
}
