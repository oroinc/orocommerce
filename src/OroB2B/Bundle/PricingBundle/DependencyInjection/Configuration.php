<?php

namespace OroB2B\Bundle\PricingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\Rounding\PriceRoundingService;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_PRICE_LISTS = 'default_price_lists';
    const ROUNDING_TYPE = 'rounding_type';
    const PRECISION = 'precision';
    const COMBINED_PRICE_LIST = 'combined_price_list';
    const FULL_COMBINED_PRICE_LIST = 'full_combined_price_list';
    const PRICE_LISTS_UPDATE_MODE = 'price_lists_update_mode';
    const OFFSET_OF_PROCESSING_CPL_PRICES = 'offset_of_processing_cpl_prices';

    /**
     * @var string
     */
    protected static $configKeyToPriceList;

    /**
     * @var string
     */
    protected static $configKeyToFullPriceList;

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
                self::COMBINED_PRICE_LIST => ['value' => null],
                self::FULL_COMBINED_PRICE_LIST => ['value' => null],
                self::PRICE_LISTS_UPDATE_MODE => ['value' => CombinedPriceListQueueConsumer::MODE_REAL_TIME],
                self::OFFSET_OF_PROCESSING_CPL_PRICES => [
                    'value' => CombinedPriceListsBuilder::DEFAULT_OFFSET_OF_PROCESSING_CPL_PRICES
                ]
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
            self::$configKeyToPriceList = self::getConfigKeyByName(Configuration::COMBINED_PRICE_LIST);
        }

        return self::$configKeyToPriceList;
    }

    /**
     * @return string
     */
    public static function getConfigKeyToFullPriceList()
    {
        if (!self::$configKeyToFullPriceList) {
            self::$configKeyToFullPriceList = self::getConfigKeyByName(Configuration::FULL_COMBINED_PRICE_LIST);
        }

        return self::$configKeyToFullPriceList;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getConfigKeyByName($key)
    {
        return implode('.', [OroB2BPricingExtension::ALIAS, $key]);
    }
}
