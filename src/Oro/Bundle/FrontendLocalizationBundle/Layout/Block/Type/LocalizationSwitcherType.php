<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;

class LocalizationSwitcherType extends AbstractType
{
    const NAME = 'localization_switcher';

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
        $resolver->setRequired(['localizations', 'selected_localization']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, Options $options)
    {
        $view->vars['localizations'] = $options['localizations'];
        $view->vars['selected_localization'] = $options['selected_localization'];
    }
}
