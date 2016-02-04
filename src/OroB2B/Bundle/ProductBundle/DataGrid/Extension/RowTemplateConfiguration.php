<?php

namespace OroB2B\Bundle\ProductBundle\DataGrid\Extension;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class RowTemplateConfiguration implements ConfigurationInterface
{
    const TEMPLATES_PATH = '[options][templates]';
    const ROW_TEMPLATE_PATH = '[options][templates][row]';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('templates')
            ->children()
                ->scalarNode('row')->end()
            ->end();

        return $builder;
    }
}
