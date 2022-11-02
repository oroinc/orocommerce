<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductCollectionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ProductCollectionCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductCollectionCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ProductCollectionCompilerPass();
    }

    public function testProcessWhenWebCatalogUsageProviderServiceFound()
    {
        $container = new ContainerBuilder();
        $container->register('oro_web_catalog.provider.web_catalog_usage_provider');
        $container->register('oro_product.form.type.extension.product_collection');

        $this->compiler->process($container);

        self::assertTrue($container->hasDefinition('oro_product.form.type.extension.product_collection'));
    }

    public function testProcessWhenWebCatalogUsageProviderServiceNotFound()
    {
        $container = new ContainerBuilder();
        $container->register('oro_product.form.type.extension.product_collection');

        $this->compiler->process($container);

        self::assertFalse($container->hasDefinition('oro_product.form.type.extension.product_collection'));
    }
}
