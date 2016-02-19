<?php

namespace OroB2B\Bundle\OrderBundle\Layout\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class BlockPrefixExtension extends AbstractBlockTypeExtension
{
    /** {@inheritdoc} */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(['block_prefixes' => []])
            ->setAllowedTypes(['block_prefixes' => 'array']);
    }

    /** {@inheritdoc} */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['block_prefixes'] = array_merge($view->vars['block_prefixes'], $options['block_prefixes']);
    }

    /** {@inheritdoc} */
    public function getExtendedType()
    {
        return BaseType::NAME;
    }
}
