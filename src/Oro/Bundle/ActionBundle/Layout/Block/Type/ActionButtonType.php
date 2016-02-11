<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class ActionButtonType extends AbstractType
{
    const NAME = 'action_button';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['params', 'fromUrl', 'actionData', 'context']);
        $resolver->setOptional(['link_class','hide_icon','only_link']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['params'] = $options['params'];
        $view->vars['fromUrl'] = $options['fromUrl'];
        $view->vars['actionData'] = $options['actionData'];
        $view->vars['context'] = $options['context'];
        if (array_key_exists('link_class', $options)) {
            $view->vars['link_class'] = $options['link_class'];
        }
        if (array_key_exists('hide_icon', $options)) {
            $view->vars['hide_icon'] = $options['hide_icon'];
        }
        if (array_key_exists('only_link', $options)) {
            $view->vars['only_link'] = $options['only_link'];
        }
    }
}
