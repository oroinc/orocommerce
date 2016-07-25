<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use Oro\DBAL\Types\MoneyType;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
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
                    'value' => MoneyType::TYPE_SCALE,
                    'scope' => 'app'
                ],
                'price_lists_update_mode' => [
                    'value' => 'real_time',
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
                'enabled_currencies' => [
                    'value' => ['USD'],
                    'scope' => 'app'
                ],
                'default_currency' => [
                    'value' => 'USD',
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
            OroB2BPricingExtension::ALIAS . '.' .Configuration::COMBINED_PRICE_LIST,
            $configKey
        );
    }
}
