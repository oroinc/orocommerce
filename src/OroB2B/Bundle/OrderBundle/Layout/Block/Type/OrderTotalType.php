<?php

namespace OroB2B\Bundle\OrderBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\Type\AbstractType;

class OrderTotalType extends AbstractType
{
    const NAME = 'order_total';

    /** {@inheritdoc} */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['total', 'subtotals']);
    }

    /** {@inheritdoc} */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['total'] = $options['total'];
        $view->vars['subtotals'] = $options['subtotals'];
    }

    /** {@inheritdoc} */
    public function getParent()
    {
        return BaseType::NAME;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }
}
