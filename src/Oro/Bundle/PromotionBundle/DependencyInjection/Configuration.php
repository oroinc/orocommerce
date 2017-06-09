<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    const FEATURE_ENABLED = 'feature_enabled';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('oro_promotion');

        SettingsBuilder::append(
            $rootNode,
            [
                self::FEATURE_ENABLED => ['type' => 'boolean', 'value' => true]
            ]
        );

        return $treeBuilder;
    }
}
