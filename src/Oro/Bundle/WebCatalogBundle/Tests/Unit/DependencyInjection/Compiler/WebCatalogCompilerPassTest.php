<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WebCatalogCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder
     */
    private $containerBuilder;

    protected function setUp()
    {
        $this->containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function tearDown()
    {
        unset($this->containerBuilder);
    }

    public function testProcessSkip()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(WebCatalogCompilerPass::WEB_CATALOG_PAGE_TYPE_REGISTRY)
            ->willReturn(false);

        $this->containerBuilder->expects($this->never())
            ->method('getDefinition')
            ->with(WebCatalogCompilerPass::WEB_CATALOG_PAGE_TYPE_REGISTRY);

        $this->containerBuilder->expects($this->never())
            ->method('findTaggedServiceIds')
            ->with(WebCatalogCompilerPass::WEB_CATALOG_PAGE_TYPE_TAG);

        $compilerPass = new WebCatalogCompilerPass();
        $compilerPass->process($this->containerBuilder);
    }

    public function testProcess()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(WebCatalogCompilerPass::WEB_CATALOG_PAGE_TYPE_REGISTRY)
            ->willReturn(true);

        $registryDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(WebCatalogCompilerPass::WEB_CATALOG_PAGE_TYPE_REGISTRY)
            ->will($this->returnValue($registryDefinition));

        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(WebCatalogCompilerPass::WEB_CATALOG_PAGE_TYPE_TAG)
            ->willReturn(['service' => ['class' => '\stdClass']]);

        $registryDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('addPageType', [new Reference('service')]);

        $compilerPass = new WebCatalogCompilerPass();
        $compilerPass->process($this->containerBuilder);
    }
}
