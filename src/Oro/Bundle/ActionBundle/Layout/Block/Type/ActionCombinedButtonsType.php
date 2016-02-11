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

        if (array_key_exists('primary_link_class', $options)) {
            $buttonOptions['link_class'] = $options['primary_link_class'];
        }

        $builder->getLayoutManipulator()->add(
            $primaryActionName . '_button_combined_primary',
            $builder->getId(),
            ActionButtonType::NAME,
            $buttonOptions
        );

        $builder->getLayoutManipulator()->add(
            'dropdown',
            $builder->getId(),
            DropdownToggleType::NAME
        );
        $builder->getLayoutManipulator()->add(
            'pizdec',
            $builder->getId(),
            ActionLineButtonsType::NAME,
            ['entity' => $options['entity'], 'suffix' => 'combined']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if (array_key_exists('ul_class', $options)) {
            $view->vars['ul_class'] = $options['ul_class'];
        }
        if (array_key_exists('div_class', $options)) {
            $view->vars['div_class'] = $options['div_class'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setOptional(['group', 'primary_link_class']);
        $resolver->setRequired(['primary_action_name', 'div_class']);
    }
}
