<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\ChainContentVariantTitleProviderCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\ContentVariantProviderCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\ContentVariantTypeCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\WebCatalogBundle\OroWebCatalogBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class OroWebCatalogBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->getMock(KernelInterface::class);

        $bundle = new OroWebCatalogBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        $this->assertInternalType('array', $passes);
        $this->assertCount(4, $passes);

        $expectedPasses = [
            new ContentVariantTypeCompilerPass(),
            new ContentVariantProviderCompilerPass(),
            new ChainContentVariantTitleProviderCompilerPass(),
            new DefaultFallbackExtensionPass([
                ContentNode::class => [
                    'title' => 'titles',
                    'slugPrototype' => 'slugPrototypes'
                ]
            ])
        ];

        foreach ($expectedPasses as $expectedPass) {
            $this->assertContains($expectedPass, $passes, '', false, false);
        }
    }

    public function testGetContainerExtension()
    {
        $bundle = new OroWebCatalogBundle();

        $this->assertInstanceOf(OroWebCatalogExtension::class, $bundle->getContainerExtension());
    }
}
