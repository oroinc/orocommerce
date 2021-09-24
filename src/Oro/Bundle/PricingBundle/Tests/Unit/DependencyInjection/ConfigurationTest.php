<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();

        $treeBuilder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $treeBuilder);
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor     = new Processor();

        $expected = [
            'settings' => [
                'resolved' => 1,
                'combined_price_list' => [
                    'value' => null,
                    'scope' => 'app'
                ],
                'default_price_lists' => [
                    'value' => [],
                    'scope' => 'app'
                ],
                'rounding_type' => [
                    'value' => RoundingServiceInterface::ROUND_HALF_UP,
                    'scope' => 'app'
                ],
                'precision' => [
                    'value' => 2,
                    'scope' => 'app'
                ],
                'offset_of_processing_cpl_prices' => [
                    'value' => 12,
                    'scope' => 'app'
                ],
                'full_combined_price_list' => [
                    'value' => null,
                    'scope' => 'app'
                ],
                'price_strategy' => [
                    'value' => MinimalPricesCombiningStrategy::NAME,
                    'scope' => 'app'
                ],
                'price_storage' => [
                    'value' => 'combined',
                    'scope' => 'app'
                ],
                'price_indexation_accuracy' => [
                    'value' => 'customer',
                    'scope' => 'app'
                ],
                'default_price_list' => [
                    'value' => null,
                    'scope' => 'app'
                ],
                'feature_enabled' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'price_calculation_precision' => [
                    'value' => null,
                    'scope' => 'app'
                ]
            ]
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }

    public function testGetConfigKeyByName()
    {
        $configKey = Configuration::getConfigKeyToPriceList();
        $this->assertSame(
            OroPricingExtension::ALIAS . '.' .Configuration::COMBINED_PRICE_LIST,
            $configKey
        );
    }
}
