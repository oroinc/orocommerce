<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogPageProviderCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogPageTypeCompilerPass;
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
        $this->assertCount(3, $passes);

        $expectedPasses = [
            new WebCatalogPageTypeCompilerPass(),
            new WebCatalogPageProviderCompilerPass(),
            new DefaultFallbackExtensionPass([
                ContentNode::class => [
                    'title' => 'titles',
                    'slug' => 'slugs'
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
