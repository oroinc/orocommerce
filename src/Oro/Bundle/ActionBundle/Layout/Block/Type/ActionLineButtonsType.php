<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ActionLineButtonsType extends AbstractButtonsType
{
    const NAME = 'action_line_buttons';

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

        foreach ($actions as $actionName => $action) {
            if (isset($options['exclude_action']) && $options['exclude_action'] === $actionName) {
                continue;
            }
            $params = $this->getParams($options, $action);
            $buttonId = $actionName . '_button';
            if (array_key_exists('suffix', $options)) {
                $buttonId .= '_' . $options['suffix'];
            }
            $buttonParameters = [
                'params' => $params,
                'context' => $options['context'],
                'fromUrl' => $options['fromUrl'],
                'actionData' => $options['actionData'],
            ];
            if (array_key_exists('hide_icons', $options)) {
                $buttonParameters['hide_icon'] = $options['hide_icons'];
            }
            $builder->getLayoutManipulator()->add(
                $buttonId,
                $builder->getId(),
                ActionButtonType::NAME,
                $buttonParameters
            );
        }
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
        $resolver->setOptional(['exclude_action', 'suffix', 'hide_icons']);
        $resolver->setRequired(['entity']);
    }
}
