<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Configuration;

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
                Configuration::ENGINE_KEY => [
                    'value' => 'orm',
                    'scope' => 'app',
                ],
                Configuration::ENGINE_PARAMETERS_KEY => [
                    'value' => [],
                    'scope' => 'app',
                ],
            ],
        ];
        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
