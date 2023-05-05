<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider configurationDataProvider
     */
    public function testProcessConfiguration(array $value, array $expected): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $this->assertEquals($expected, $processor->processConfiguration($configuration, $value));
    }

    public function configurationDataProvider(): array
    {
        return [
            'Empty config' => [
                'value' => [],
                'expected' => [
                    'allowed_ips' => []
                ]
            ],
            'Valid config' => [
                'value' => [
                    'oro_paypal' => [
                        'allowed_ips' => [
                            '255.255.255.1',
                            '255.255.255.0/24',
                            '2001:db8::85a3:0:8a2e:370:7334',
                            '2001:db8::85a3:0:8a2e:370:7334/64'
                        ]
                    ],
                ],
                'expected' => [
                    'allowed_ips' => [
                        '255.255.255.1',
                        '255.255.255.0/24',
                        '2001:db8::85a3:0:8a2e:370:7334',
                        '2001:db8::85a3:0:8a2e:370:7334/64'
                    ]
                ]
            ]
        ];
    }

    public function testProcessConfigurationWithInvalidConfig(): void
    {
        $invalidIPs = [
            'invalid_ip',
            '255.255.255.O/24',
            '256.256.256.256',
            '256.256.256.256/25',
            '2001:db8::85a3:0:8a2e:370:7334:1',
            '2001:db8::85a3:0:8a2e:370:7334/129',
        ];

        $validIPs = [
            '255.255.255.0',
            '255.255.255.0/24',
            '2001:db8::85a3:0:8a2e:370:7334',
            '2001:db8::85a3:0:8a2e:370:7334/64'
        ];

        $configuration = new Configuration();
        $processor = new Processor();
        $data = ['oro_paypal' => ['allowed_ips' => array_merge($invalidIPs, $validIPs)]];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            sprintf('The following IP addresses are invalid: %s', implode(', ', $invalidIPs))
        );
        $processor->processConfiguration($configuration, $data);
    }
}
