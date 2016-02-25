<?php

namespace OroB2B\Bundle\ProductBundle\DataGrid\Extension\Theme;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const THEME_PATH = '[options][theme]';
    const ROW_VIEW_PATH = '[options][theme][rowView]';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('theme')
            ->children()
                ->scalarNode('rowView')->end()
            ->end();

        return $builder;
    }
}
