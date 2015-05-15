<?php

namespace Oro\Bundle\CurrencyBundle\DependencyInjection;

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

        $rootNode = $treeBuilder->root('oro_currency');

        SettingsBuilder::append(
            $rootNode,
            [
                'allowed_currencies' => ['value' => [], 'type' => 'array'],
            ]
        );

        return $treeBuilder;
    }
}
