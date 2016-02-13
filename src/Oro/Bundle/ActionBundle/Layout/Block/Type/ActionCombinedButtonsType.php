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

        $builder->getLayoutManipulator()->add(
            $primaryActionName . '_button_combined_primary',
            $builder->getId(),
            ActionButtonType::NAME,
            $buttonOptions
        );

        $builder->getLayoutManipulator()->add(
            'action_dropdown_link',
            $builder->getId(),
            DropdownToggleType::NAME
        );

        $lineButtonsOptions=[
            'entity' => $options['entity'],
            'suffix' => 'combined',
            'hide_icons' => true,
            'exclude_action' => $primaryActionName
        ];

        $builder->getLayoutManipulator()->add(
            'action_dropdown_menu',
            $builder->getId(),
            ActionLineButtonsType::NAME,
            $lineButtonsOptions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['attr'] = ['data-page-component-module' => 'oroaction/js/app/components/buttons-component'];
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
