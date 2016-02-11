<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DropdownToggleType extends AbstractType
{
    const NAME = 'action_dropdown_toggle';

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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['class']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if (array_key_exists('class', $options)) {
            $view->vars['class'] = $options['class'];
        }
    }
}
