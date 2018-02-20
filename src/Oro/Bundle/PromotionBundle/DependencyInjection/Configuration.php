<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const FEATURE_ENABLED = 'feature_enabled';
    const DISCOUNT_STRATEGY = 'discount_strategy';

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
                self::FEATURE_ENABLED => ['type' => 'boolean', 'value' => true],
                self::DISCOUNT_STRATEGY => ['type' => 'string', 'value' => 'apply_all']
            ]
        );

        return $treeBuilder;
    }
}
