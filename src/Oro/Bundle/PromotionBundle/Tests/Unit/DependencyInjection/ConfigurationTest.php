<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PromotionBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Configuration
     */
    protected $configuration;

    protected function setUp(): void
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
                'resolved' => true,
                Configuration::FEATURE_ENABLED => [
                    'value' => true,
                    'scope' => 'app'
                ],
                Configuration::DISCOUNT_STRATEGY => [
                    'value' => 'apply_all',
                    'scope' => 'app'
                ],
                Configuration::CASE_INSENSITIVE_COUPON_SEARCH => [
                    'value' => false,
                    'scope' => 'app'
                ],
            ]
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
