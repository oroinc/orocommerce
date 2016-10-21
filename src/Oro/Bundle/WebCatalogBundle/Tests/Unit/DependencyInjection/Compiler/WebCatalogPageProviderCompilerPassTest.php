<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogPageProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WebCatalogPageProviderCompilerPassTest extends \PHPUnit_Framework_TestCase
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

    public function testProcess()
    {
        /* @var $compilerPass WebCatalogPageProviderCompilerPass|\PHPUnit_Framework_MockObject_MockObject */
        $compilerPass = $this->getMockBuilder(WebCatalogPageProviderCompilerPass::class)
            ->setMethods(['registerTaggedServices'])
            ->getMock();

        $compilerPass->expects($this->once())
            ->method('registerTaggedServices')
            ->with(
                $this->containerBuilder,
                WebCatalogPageProviderCompilerPass::WEB_CATALOG_PAGE_PROVIDER_REGISTRY,
                WebCatalogPageProviderCompilerPass::WEB_CATALOG_PAGE_PROVIDER_TAG,
                'addPageProvider'
            );

        $compilerPass->process($this->containerBuilder);
    }
}
