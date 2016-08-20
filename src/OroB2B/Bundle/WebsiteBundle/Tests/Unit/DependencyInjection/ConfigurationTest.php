<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\WebsiteBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $builder);
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $expected = [
            'settings' => [
                'resolved' => true,
                Configuration::URL => [
                    'value' => '',
                    'scope' => 'app',
                ],
                Configuration::SECURE_URL => [
                    'value' => '',
                    'scope' => 'app',
                ],
            ],
        ];
        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
