<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PaymentBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $configuration = new Configuration();

        $this->assertEquals($expected, (new Processor())->processConfiguration($configuration, $configs));
    }

    public function processConfigurationDataProvider(): array
    {
        return [
            'empty' => [
                'configs'  => [[]],
                'expected' => [
                    'settings' => [
                        'resolved' => true,
                        'merchant_country' => [
                            'value' => 'US',
                            'scope' => 'app'
                        ],
                    ]
                ]
            ]
        ];
    }
}
