<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogPageTypeCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WebCatalogPageTypeCompilerPassTest extends \PHPUnit_Framework_TestCase
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
        /* @var $compilerPass WebCatalogPageTypeCompilerPass|\PHPUnit_Framework_MockObject_MockObject */
        $compilerPass = $this->getMockBuilder(WebCatalogPageTypeCompilerPass::class)
            ->setMethods(['registerTaggedServices'])
            ->getMock();

        $compilerPass->expects($this->once())
            ->method('registerTaggedServices')
            ->with(
                $this->containerBuilder,
                WebCatalogPageTypeCompilerPass::WEB_CATALOG_PAGE_TYPE_REGISTRY,
                WebCatalogPageTypeCompilerPass::WEB_CATALOG_PAGE_TYPE_TAG,
                'addPageType'
            );

        $compilerPass->process($this->containerBuilder);
    }
}
