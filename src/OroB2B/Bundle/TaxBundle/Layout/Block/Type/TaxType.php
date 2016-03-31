<?php

namespace OroB2B\Bundle\TaxBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class TaxType extends AbstractType
{
    const NAME = 'tax';

    /** {@inheritdoc} */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['result']);
    }

    /** {@inheritdoc} */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['result'] = $options['result'];
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }
}
