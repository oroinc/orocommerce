<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const FEATURE_ENABLED = 'feature_enabled';
    public const DISCOUNT_STRATEGY = 'discount_strategy';
    public const CASE_INSENSITIVE_COUPON_SEARCH = 'case_insensitive_coupon_search';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_promotion');

        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::FEATURE_ENABLED => ['type' => 'boolean', 'value' => true],
                self::DISCOUNT_STRATEGY => ['type' => 'string', 'value' => 'apply_all'],
                self::CASE_INSENSITIVE_COUPON_SEARCH => ['type' => 'boolean', 'value' => false]
            ]
        );

        return $treeBuilder;
    }
}
