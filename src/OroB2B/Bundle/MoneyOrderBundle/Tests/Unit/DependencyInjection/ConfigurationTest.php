<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Tests\Unit\DependencyInjection;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration as PaymentConfiguration;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;

use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $this->assertInstanceOf(
            'Symfony\Component\Config\Definition\Builder\TreeBuilder',
            $configuration->getConfigTreeBuilder()
        );

        $builder = $configuration->getConfigTreeBuilder();
        $root = $builder->buildTree();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\ArrayNode', $root);
        $this->assertEquals(OroB2BMoneyOrderExtension::ALIAS, $root->getName());
    }

    /**
     * @dataProvider processConfigurationDataProvider
     * @param array $configs
     * @param array $expected
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    /**
     * @return array
     */
    public function processConfigurationDataProvider()
    {
        return [
            'empty' => [
                'configs'  => [[]],
                'expected' => [
                    'settings' => [
                        'resolved'                                        => true,
                        Configuration::MONEY_ORDER_ENABLED_KEY            => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        Configuration::MONEY_ORDER_LABEL_KEY              => [
                            'value' => Configuration::MONEY_ORDER_LABEL,
                            'scope' => 'app'
                        ],
                        Configuration::MONEY_ORDER_SORT_ORDER_KEY         => [
                            'value' => Configuration::MONEY_ORDER_SORT_ORDER,
                            'scope' => 'app'
                        ],
                        Configuration::MONEY_ORDER_PAY_TO_KEY             => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        Configuration::MONEY_ORDER_SEND_TO_KEY            => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        Configuration::MONEY_ORDER_ALLOWED_COUNTRIES_KEY  => [
                            'value' => PaymentConfiguration::ALLOWED_COUNTRIES_ALL,
                            'scope' => 'app'
                        ],
                        Configuration::MONEY_ORDER_SELECTED_COUNTRIES_KEY => [
                            'value' => [],
                            'scope' => 'app'
                        ],
                    ]
                ]
            ]
        ];
    }
}
