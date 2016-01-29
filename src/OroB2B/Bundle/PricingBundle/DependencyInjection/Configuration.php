<?php

namespace OroB2B\Bundle\PricingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\PricingBundle\Rounding\PriceRoundingService;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_PRICE_LISTS = 'default_price_lists';
    const ROUNDING_TYPE = 'rounding_type';
    const PRECISION = 'precision';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroB2BPricingExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                self::DEFAULT_PRICE_LISTS => [ 'type' => 'array', 'value' => []],
                self::ROUNDING_TYPE => ['value' => PriceRoundingService::ROUND_HALF_UP],
                self::PRECISION => ['value' => PriceRoundingService::FALLBACK_PRECISION],
            ]
        );

        return $treeBuilder;
    }
}
