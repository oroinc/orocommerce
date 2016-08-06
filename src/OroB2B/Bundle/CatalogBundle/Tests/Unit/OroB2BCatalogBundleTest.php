<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\OroB2BCatalogBundle;

class OroB2BCatalogBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle = new OroB2BCatalogBundle($kernel);
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
