<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SEOBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $this->assertInstanceOf(
            TreeBuilder::class,
            $configuration->getConfigTreeBuilder()
        );
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $expected = [
            'settings' => [
                'resolved' => true,
                'sitemap_changefreq_default' => [
                    'value' => Configuration::CHANGEFREQ_DAILY,
                    'scope' => 'app',
                ],
                'sitemap_changefreq_product' => [
                    'value' => Configuration::CHANGEFREQ_DAILY,
                    'scope' => 'app',
                ],
                'sitemap_priority_product' => [
                    'value' => 0.5,
                    'scope' => 'app',
                ],
                'sitemap_changefreq_category' => [
                    'value' => Configuration::CHANGEFREQ_DAILY,
                    'scope' => 'app',
                ],
                'sitemap_priority_category' => [
                    'value' => 0.5,
                    'scope' => 'app',
                ],
                'sitemap_changefreq_page' => [
                    'value' => Configuration::CHANGEFREQ_DAILY,
                    'scope' => 'app',
                ],
                'sitemap_priority_page' => [
                    'value' => 0.5,
                    'scope' => 'app',
                ],
            ],
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
