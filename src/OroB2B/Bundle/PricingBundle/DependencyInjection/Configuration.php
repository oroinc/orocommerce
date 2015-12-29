<?php

namespace OroB2B\Bundle\PricingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\PricingBundle\Rounding\PriceRoundingService;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_PRICE_LISTS = 'default_price_lists';
    const COMBINED_PRICE_LIST = 'combined_price_list';

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
                self::COMBINED_PRICE_LIST => null,
                self::DEFAULT_PRICE_LISTS => [ 'type' => 'array', 'value' => []],
                'rounding_type' => ['value' => PriceRoundingService::HALF_UP],
                'precision' => ['value' => PriceRoundingService::FALLBACK_PRECISION],
            ]
        );

        return $treeBuilder;
    }
}
