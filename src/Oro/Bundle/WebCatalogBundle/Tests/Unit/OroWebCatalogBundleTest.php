<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\WebCatalogBundle\OroWebCatalogBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroWebCatalogBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle = new OroWebCatalogBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        $this->assertInternalType('array', $passes);
        $this->assertCount(1, $passes);
        $this->assertInstanceOf(DefaultFallbackExtensionPass::class, $passes[0]);
        $this->assertAttributeEquals(
            [
                ContentNode::class => [
                    'title' => 'titles',
                    'slug' => 'slugs'
                ]
            ],
            'classes',
            $passes[0]
        );
    }

    public function testGetContainerExtension()
    {
        $bundle = new OroWebCatalogBundle();

        $this->assertInstanceOf(OroWebCatalogExtension::class, $bundle->getContainerExtension());
    }
}
