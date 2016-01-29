<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\DependencyInjection;

use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use Symfony\Component\Config\Definition\Processor;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

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
                    'value' => 'half_up',
                    'scope' => 'app'
                ],
                'precision' => [
                    'value' => 4,
                    'scope' => 'app'
                ],
                'price_lists_update_mode' => [
                    'value' => 'scheduled',
                    'scope' => 'app'
                ],
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
