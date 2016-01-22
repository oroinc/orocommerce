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
     * @var
     */
    protected static $configKeyToPriceList;

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
                self::DEFAULT_PRICE_LISTS => ['type' => 'array', 'value' => []],
                'rounding_type' => ['value' => PriceRoundingService::HALF_UP],
                'precision' => ['value' => PriceRoundingService::FALLBACK_PRECISION],
                'price_lists_update_strategy' => ['value' => 'scheduled'],
            ]
        );

        return $treeBuilder;
    }

    /**
     * @return string
     */
    public static function getConfigKeyToPriceList()
    {
        if (!self::$configKeyToPriceList) {
            self::$configKeyToPriceList = implode(
                '.',
                [OroB2BPricingExtension::ALIAS, Configuration::COMBINED_PRICE_LIST]
            );
        }

        return self::$configKeyToPriceList;
    }
}
