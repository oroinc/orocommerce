<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ContentNodeFieldsChangesCompilerPass;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\OroSEOBundle;

class OroSEOBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $bundle = new OroSEOBundle($this->createMock(KernelInterface::class));
        $bundle->build($container);

        $fields = [
            'metaDescription' => 'metaDescriptions',
            'metaKeyword' => 'metaKeywords'
        ];

        $this->assertEquals(
            [
                new DefaultFallbackExtensionPass([
                    Product::class => $fields,
                    Category::class => $fields,
                    Page::class => $fields,
                ]),
                new ContentNodeFieldsChangesCompilerPass(
                    array_values($fields),
                    'oro_product.event_listener.product_content_variant_reindex'
                ),
                new ContentNodeFieldsChangesCompilerPass(
                    array_values($fields),
                    'oro_catalog.event_listener.category_content_variant_index'
                ),
            ],
            $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses()
        );
    }
}
