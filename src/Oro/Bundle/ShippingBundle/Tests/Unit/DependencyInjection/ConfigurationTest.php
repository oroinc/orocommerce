<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ShippingBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /** @var Configuration */
    protected $configuration;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
    }

    protected function tearDown(): void
    {
        unset($this->configuration);
    }

    public function testGetConfigTreeBuilder()
    {
        $this->assertInstanceOf(
            'Symfony\Component\Config\Definition\Builder\TreeBuilder',
            $this->configuration->getConfigTreeBuilder()
        );
    }

    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $config, array $expected)
    {
        $processor = new Processor();

        $this->assertEquals($expected, $processor->processConfiguration($this->configuration, $config));
    }

    /**
     * @return array
     */
    public function processConfigurationDataProvider()
    {
        return [
            [
                'config'  => [],
                'expected' => [
                    'settings' => [
                        'resolved' => true,
                        'shipping_origin' => ['value' => [], 'scope' => 'app'],
                        'length_units' => ['value' => ['inch', 'foot', 'cm', 'm'], 'scope' => 'app'],
                        'weight_units' => ['value' => ['lbs', 'kg'], 'scope' => 'app'],
                        'freight_classes' => ['value' => ['parcel'], 'scope' => 'app'],
                    ]
                ]
            ]
        ];
    }
}
