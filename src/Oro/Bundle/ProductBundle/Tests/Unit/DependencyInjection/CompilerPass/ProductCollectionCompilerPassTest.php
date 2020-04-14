<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductCollectionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ProductCollectionCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductCollectionCompilerPass
     */
    private $productCollectionCompilerPass;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder
     */
    private $container;

    protected function setUp(): void
    {
        $this->container = $this->getMockBuilder(ContainerBuilder::class)->getMock();

        $this->productCollectionCompilerPass = new ProductCollectionCompilerPass();
    }

    public function testProcessWhenWebCatalogUsageProviderServiceFound()
    {
        $this->container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_web_catalog.provider.web_catalog_usage_provider')
            ->willReturn(true);

        $this->container
            ->expects($this->never())
            ->method('removeDefinition');

        $this->productCollectionCompilerPass->process($this->container);
    }

    public function testProcessWhenNoWebCatalogUsageProviderServiceFound()
    {
        $this->container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_web_catalog.provider.web_catalog_usage_provider')
            ->willReturn(false);

        $this->container
            ->expects($this->once())
            ->method('removeDefinition')
            ->with('oro_product.form.type.extension.product_collection');

        $this->productCollectionCompilerPass->process($this->container);
    }
}
