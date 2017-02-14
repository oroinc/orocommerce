<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();

        $treeBuilder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor     = new Processor();

        $expected = [
            'settings' => [
                'resolved' => 1,
                'enable_direct_url' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'canonical_url_type' => [
                    'value' => 'system',
                    'scope' => 'app'
                ],
                'canonical_url_security_type' => [
                    'value' => 'insecure',
                    'scope' => 'app'
                ],
            ]
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
