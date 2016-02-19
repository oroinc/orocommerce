<?php

namespace OroB2B\Bundle\OrderBundle\Layout;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\TextType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\Type\AbstractType;

class CurrencyType extends AbstractType
{
    const NAME = 'currency';

    /** {@inheritdoc} */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['currency'])
            ->setDefaults(
                [
                    'attributes' => [],
                    'textAttributes' => [],
                    'symbols' => [],
                    'locale' => null,
                ]
            );
    }

    /** {@inheritdoc} */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['currency'] = $options['currency'];
        $view->vars['attributes'] = $options['attributes'];
        $view->vars['textAttributes'] = $options['textAttributes'];
        $view->vars['symbols'] = $options['symbols'];
        $view->vars['locale'] = $options['locale'];
    }

    /** {@inheritdoc} */
    public function getParent()
    {
        return TextType::NAME;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }
}
