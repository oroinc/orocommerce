<?php

namespace Oro\Bundle\PricingBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\CurrencyBundle\Rounding\PriceRoundingService;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'oro_pricing';
    const DEFAULT_PRICE_LISTS = 'default_price_lists';
    const DEFAULT_PRICE_LIST = 'default_price_list';
    const PRICE_STORAGE = 'price_storage';

    /**
     * price_indexation_accuracy config option regulates the accuracy of search indexation. Supported values:
     * customer, customer_group, website
     * Most accurate results will be for customer level, prices will be indexed per each
     * price list to customer association. Requires maximum amount of search index storage.
     * customer_group accuracy provides moderate level of search accuracy and requires less amount of storage
     * Less accurate but most storage efficient accuracy is website - when only price lists associated with website
     * are taken into account during prices indexation
     */
    const PRICE_INDEXATION_ACCURACY = 'price_indexation_accuracy';
    const ROUNDING_TYPE = 'rounding_type';
    const PRECISION = 'precision';
    const COMBINED_PRICE_LIST = 'combined_price_list';
    const FULL_COMBINED_PRICE_LIST = 'full_combined_price_list';
    const OFFSET_OF_PROCESSING_CPL_PRICES = 'offset_of_processing_cpl_prices';
    const PRICE_LIST_STRATEGIES = 'price_strategy';
    const PRICE_CALCULATION_PRECISION = 'price_calculation_precision';
    const DEFAULT_OFFSET_OF_PROCESSING_CPL_PRICES = 12.0;

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
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);

        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::DEFAULT_PRICE_LISTS => ['type' => 'array', 'value' => []],
                self::DEFAULT_PRICE_LIST => ['type' => 'integer', 'value' => null],
                self::PRICE_STORAGE => ['type' => 'string', 'value' => 'combined'],
                self::PRICE_INDEXATION_ACCURACY => ['type' => 'string', 'value' => 'customer'],
                self::ROUNDING_TYPE => ['value' => PriceRoundingService::ROUND_HALF_UP],
                self::PRECISION => ['value' => PriceRoundingService::DEFAULT_PRECISION],
                self::COMBINED_PRICE_LIST => ['value' => null],
                self::FULL_COMBINED_PRICE_LIST => ['value' => null],
                self::OFFSET_OF_PROCESSING_CPL_PRICES => ['value' => self::DEFAULT_OFFSET_OF_PROCESSING_CPL_PRICES],
                self::PRICE_LIST_STRATEGIES => ['type' => 'string', 'value' => MinimalPricesCombiningStrategy::NAME],
                'feature_enabled' => ['value' => true],
                self::PRICE_CALCULATION_PRECISION => ['type' => 'integer', 'value' => null],
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
            self::$configKeyToPriceList = self::getConfigKeyByName(self::COMBINED_PRICE_LIST);
        }

        return self::$configKeyToPriceList;
    }

    /**
     * @return string
     */
    public static function getConfigKeyToFullPriceList()
    {
        if (!self::$configKeyToFullPriceList) {
            self::$configKeyToFullPriceList = self::getConfigKeyByName(self::FULL_COMBINED_PRICE_LIST);
        }

        return self::$configKeyToFullPriceList;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getConfigKeyByName($key)
    {
        return self::ROOT_NODE . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }
}
