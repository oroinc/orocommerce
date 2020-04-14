<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit;

use Oro\Bundle\CatalogBundle\DependencyInjection\CompilerPass\AttributeBlockTypeMapperPass;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\OroCatalogBundle;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCatalogBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->createMock('Symfony\Component\HttpKernel\KernelInterface');

        $passesBeforeBuild = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        $bundle = new OroCatalogBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        // Remove default passes from array
        $passes = array_values(array_filter($passes, function ($pass) use ($passesBeforeBuild) {
            return !in_array($pass, $passesBeforeBuild, true);
        }));

        $this->assertIsArray($passes);
        $this->assertCount(2, $passes);
        $this->assertInstanceOf(DefaultFallbackExtensionPass::class, $passes[0]);
        $this->assertAttributeEquals(
            [
                Category::class => [
                    'title' => 'titles',
                    'shortDescription' => 'shortDescriptions',
                    'longDescription' => 'longDescriptions',
                    'slugPrototype' => 'slugPrototypes'
                ]
            ],
            'classes',
            $passes[0]
        );
        $this->assertInstanceOf(AttributeBlockTypeMapperPass::class, $passes[1]);
    }
}
