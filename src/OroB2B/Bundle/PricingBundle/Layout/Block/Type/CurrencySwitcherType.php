<?php

namespace OroB2B\Bundle\PricingBundle\Layout\Block\Type;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\Block\Type\AbstractType;

class CurrencySwitcherType extends AbstractType
{
    const NAME = 'currency_switcher';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['currencies', 'selected_currency']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['currencies'] = $options['currencies'];
        $view->vars['selected_currency'] = $options['selected_currency'];
    }
}
