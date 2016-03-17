<?php

namespace OroB2B\Bundle\OrderBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\Type\AbstractType;

class AddressType extends AbstractType
{
    const NAME = 'address';

    /** {@inheritdoc} */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['address']);
    }

    /** {@inheritdoc} */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['address'] = $options['address'];
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }
}
