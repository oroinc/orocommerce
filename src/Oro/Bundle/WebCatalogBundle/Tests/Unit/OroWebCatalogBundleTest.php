<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogCompilerPass;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
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
        $this->assertInstanceOf(WebCatalogCompilerPass::class, $passes[0]);
    }

    public function testGetContainerExtension()
    {
        $bundle = new OroWebCatalogBundle();

        $this->assertInstanceOf(OroWebCatalogExtension::class, $bundle->getContainerExtension());
    }
}
