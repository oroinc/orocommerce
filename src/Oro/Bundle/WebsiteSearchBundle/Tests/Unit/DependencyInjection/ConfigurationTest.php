<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\SearchBundle\DependencyInjection\Configuration as SearchConfiguration;
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
        $processor = new Processor();
        $expected = [
            Configuration::ENGINE_KEY => SearchConfiguration::DEFAULT_ENGINE,
            Configuration::ENGINE_PARAMETERS_KEY => []
        ];
        $this->assertEquals($expected, $processor->processConfiguration(new Configuration(), []));
    }
}
