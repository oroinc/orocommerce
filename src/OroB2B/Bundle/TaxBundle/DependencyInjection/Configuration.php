<?php

namespace OroB2B\Bundle\TaxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroB2BTaxExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'tax_enable' => ['value' => null],
                'tax_provider' => ['value' => null],
            ]
        );

        return $treeBuilder;
    }
}
