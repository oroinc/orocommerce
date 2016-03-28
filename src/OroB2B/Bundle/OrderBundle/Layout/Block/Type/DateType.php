<?php

namespace OroB2B\Bundle\OrderBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\Type\AbstractType;

class DateType extends AbstractType
{
    const NAME = 'date';

    /** {@inheritdoc} */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['date'])
            ->setDefaults(
                [
                    'dateType' => null,
                    'locale' => null,
                    'timeZone' => null,
                ]
            );
    }

    /** {@inheritdoc} */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['date'] = $options['date'];
        $view->vars['dateType'] = $options['dateType'];
        $view->vars['locale'] = $options['locale'];
        $view->vars['timeZone'] = $options['timeZone'];
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }
}
