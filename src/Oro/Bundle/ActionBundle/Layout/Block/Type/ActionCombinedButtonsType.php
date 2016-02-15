<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Oro\Bundle\ActionBundle\Exception\ActionNotFoundException;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ActionCombinedButtonsType extends AbstractButtonsType
{
    const NAME = 'action_combined_buttons';
    const PRIMARY_BUTTON_SUFFIX = 'button_combined_primary';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, array $options)
    {
        $options = $this->setActionParameters($options);
        $actions = $this->getActions($options);
        if (!array_key_exists($options['primary_action_name'], $actions)) {
            throw new ActionNotFoundException($options['primary_action_name']);
        }
        $primaryActionName = $options['primary_action_name'];
        $primaryAction = $actions[$primaryActionName];
        $params = $this->getParams($options, $primaryAction);

        $buttonOptions = [
            'params' => $params,
            'context' => $options['context'],
            'fromUrl' => $options['fromUrl'],
            'actionData' => $options['actionData'],
            'hide_icon' => true,
            'only_link' => true
        ];
        $layoutManipulator = $builder->getLayoutManipulator();
        $builderId = $builder->getId();

        $layoutManipulator->add(
            $primaryActionName . '_' . self::PRIMARY_BUTTON_SUFFIX,
            $builderId,
            ActionButtonType::NAME,
            $buttonOptions
        );

        $layoutManipulator->add('action_dropdown_link', $builderId, DropdownToggleType::NAME);

        $lineButtonsOptions = [
            'entity' => $options['entity'],
            'suffix' => 'combined',
            'hide_icons' => true,
            'exclude_action' => $primaryActionName
        ];

        $layoutManipulator->add('action_dropdown_menu', $builderId, ActionLineButtonsType::NAME, $lineButtonsOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['attr'] = ['data-page-component-module' => 'oroaction/js/app/components/buttons-component'];
        $view->vars['primary_button_alias'] = $options['primary_action_name'] . '_' . self::PRIMARY_BUTTON_SUFFIX;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setRequired(['primary_action_name']);
    }
}
