<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ComponentProcessorPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\DefaultProductUnitProvidersCompilerPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductCollectionCompilerPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductDataStorageSessionBagPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\OroProductBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroProductBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->createMock('Symfony\Component\HttpKernel\KernelInterface');

        $passesBeforeBuild = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        $bundle = new OroProductBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        // Remove default passes from array
        $passes = array_values(array_filter($passes, function ($pass) use ($passesBeforeBuild) {
            return !in_array($pass, $passesBeforeBuild, true);
        }));

        $this->assertInternalType('array', $passes);
        $this->assertCount(6, $passes);
        $this->assertInstanceOf(ComponentProcessorPass::class, $passes[0]);
        $this->assertInstanceOf(ProductDataStorageSessionBagPass::class, $passes[1]);
        $this->assertInstanceOf(TwigSandboxConfigurationPass::class, $passes[2]);
        $this->assertInstanceOf(DefaultProductUnitProvidersCompilerPass::class, $passes[3]);
        $this->assertInstanceOf(DefaultFallbackExtensionPass::class, $passes[4]);
        $this->assertInstanceOf(ProductCollectionCompilerPass::class, $passes[5]);
        $this->assertAttributeEquals(
            [
                Product::class => [
                    'name' => 'names',
                    'description' => 'descriptions',
                    'shortDescription' => 'shortDescriptions',
                    'slugPrototype' => 'slugPrototypes'
                ],
                Brand::class => [
                    'name' => 'names',
                    'description' => 'descriptions',
                    'shortDescription' => 'shortDescriptions',
                    'slugPrototype' => 'slugPrototypes'
                ]
            ],
            'classes',
            $passes[4]
        );
    }
}
