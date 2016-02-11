<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

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
            $params = $this->getParams($options, $action);

            $builder->getLayoutManipulator()->add(
                $actionName . '_button',
                $builder->getId(),
                ActionButtonType::NAME,
                [
                    'params' => $params,
                    'context' => $options['context'],
                    'fromUrl' => $options['fromUrl'],
                    'actionData' => $options['actionData']
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if (array_key_exists('ul_class', $options)) {
            $view->vars['ul_class'] = $options['ul_class'];
        }
    }
}
