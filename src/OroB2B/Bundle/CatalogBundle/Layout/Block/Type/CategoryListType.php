<?php

namespace OroB2B\Bundle\CatalogBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class CategoryListType extends AbstractContainerType
{
    const NAME = 'category_list';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['categories']);
        $resolver->setDefaults(
            [
                'max_size' => null // max amount of categories that will be rendered
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['categories'] = $options['categories'];
        $view->vars['max_size'] = $options['max_size'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
