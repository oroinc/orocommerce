<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\ActionBundle\Layout\Block\Type\ActionButtonType;
use Oro\Bundle\ActionBundle\Layout\Block\Type\ActionDropDownButtons;
use Oro\Bundle\ActionBundle\Layout\Block\Type\ActionLineButtonsType;
use Oro\Bundle\ActionBundle\Layout\Block\Type\DropdownToggleType;
use Oro\Component\Layout\LayoutManipulatorInterface;

class ActionCombinedButtonsTypeTest extends AbstractActionButtonsTypeTest
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

        $options['primary_action_name'] = 'action1';
        $builderId = 'builder';
        /** @var LayoutManipulatorInterface|\PHPUnit_Framework_MockObject_MockObject $manipulator */
        $manipulator = $this->getMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $expectedOptions['hide_icon'] = true;

        $manipulator->expects($this->exactly(2))->method('add')->withConsecutive(
            [
                $builderId . '_button_combined_primary',
                $builderId,
                ActionButtonType::NAME,
                $expectedOptions
            ],
            [
                $builderId . '_action_dropdown_menu',
                $builderId,
                ActionDropDownButtons::NAME,
                [
                    'entity' => $options['entity'],
                    'exclude_action' => $options['primary_action_name']
                ]
            ]
        );
        $blockBuilder = $this->getBlockBuilder($manipulator, $builderId);

        $this->blockType->buildBlock($blockBuilder, $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTypeClassName()
    {
        return 'Oro\Bundle\ActionBundle\Layout\Block\Type\ActionCombinedButtonsType';
    }

    /**
     * {@inheritDoc}
     */
    protected function setResolverExpectations($resolver)
    {
        $resolver->expects($this->at(3))->method('setRequired')->with(['primary_action_name']);
    }
}
