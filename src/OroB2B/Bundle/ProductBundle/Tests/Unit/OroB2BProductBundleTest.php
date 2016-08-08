<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\DependencyInjection\CompilerPass\ComponentProcessorPass;
use OroB2B\Bundle\ProductBundle\DependencyInjection\CompilerPass\DefaultProductUnitProvidersCompilerPass;
use OroB2B\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductDataStorageSessionBagPass;
use OroB2B\Bundle\ProductBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass;
use OroB2B\Bundle\ProductBundle\OroB2BProductBundle;

class OroB2BProductBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle = new OroB2BProductBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        $this->assertInternalType('array', $passes);
        $this->assertCount(5, $passes);
        $this->assertInstanceOf(ComponentProcessorPass::class, $passes[0]);
        $this->assertInstanceOf(ProductDataStorageSessionBagPass::class, $passes[1]);
        $this->assertInstanceOf(TwigSandboxConfigurationPass::class, $passes[2]);
        $this->assertInstanceOf(DefaultProductUnitProvidersCompilerPass::class, $passes[3]);
        $this->assertInstanceOf(DefaultFallbackExtensionPass::class, $passes[4]);
        $this->assertAttributeEquals(
            [
                Product::class => [
                    'name' => 'names',
                    'description' => 'descriptions',
                    'shortDescription' => 'shortDescriptions'
                ]
            ],
            'classes',
            $passes[4]
        );
    }
}
