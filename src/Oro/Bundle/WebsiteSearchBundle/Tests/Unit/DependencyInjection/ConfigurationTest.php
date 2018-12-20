<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SearchBundle\DependencyInjection\Configuration as SearchConfiguration;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
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
