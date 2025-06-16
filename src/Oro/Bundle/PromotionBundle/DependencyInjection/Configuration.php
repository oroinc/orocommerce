<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides configuration settings for the oro_promotion bundle.
 */
class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_promotion';
    public const FEATURE_ENABLED = 'feature_enabled';
    public const DISCOUNT_STRATEGY = 'discount_strategy';
    public const CASE_INSENSITIVE_COUPON_SEARCH = 'case_insensitive_coupon_search';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);

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

    public static function getConfigKey(string $name): string
    {
        return TreeUtils::getConfigKey(static::ROOT_NODE, $name);
    }
}
