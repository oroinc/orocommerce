<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\OroCatalogBundle;

class OroCatalogBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle = new OroCatalogBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        $this->assertInternalType('array', $passes);
        $this->assertCount(1, $passes);
        $this->assertInstanceOf(DefaultFallbackExtensionPass::class, $passes[0]);
        $this->assertAttributeEquals(
            [
                Category::class => [
                    'title' => 'titles',
                    'shortDescription' => 'shortDescriptions',
                    'longDescription' => 'longDescriptions'
                ]
            ],
            'classes',
            $passes[0]
        );
    }
}
