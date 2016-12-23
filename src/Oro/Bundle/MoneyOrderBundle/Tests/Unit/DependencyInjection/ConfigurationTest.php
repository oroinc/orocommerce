<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;
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
        $this->assertEquals(OroMoneyOrderExtension::ALIAS, $root->getName());
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
                'configs' => [[]],
                'expected' => [
                    'settings' => [
                        'resolved' => true,
                        Configuration::MONEY_ORDER_LABEL_KEY => [
                            'value' => Configuration::MONEY_ORDER_LABEL,
                            'scope' => 'app'
                        ],
                        Configuration::MONEY_ORDER_SHORT_LABEL_KEY => [
                            'value' => Configuration::MONEY_ORDER_LABEL,
                            'scope' => 'app'
                        ],
                        Configuration::MONEY_ORDER_PAY_TO_KEY => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        Configuration::MONEY_ORDER_SEND_TO_KEY => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                    ]
                ]
            ]
        ];
    }
}
