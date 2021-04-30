<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ContentNodeFieldsChangesCompilerPass;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\DependencyInjection\Compiler\FullListUrlProvidersCompilerPass;
use Oro\Bundle\SEOBundle\DependencyInjection\Compiler\MigrateFileStorageCommandCompilerPass;
use Oro\Bundle\SEOBundle\DependencyInjection\Compiler\UrlItemsProviderCompilerPass;
use Oro\Bundle\SEOBundle\OroSEOBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class OroSEOBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $passesBeforeBuild = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        $bundle = new OroSEOBundle($this->createMock(KernelInterface::class));
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        // Remove default passes from array
        $passes = array_values(array_filter($passes, function ($pass) use ($passesBeforeBuild) {
            return !in_array($pass, $passesBeforeBuild, true);
        }));

        $fields = [
            'metaTitle' => 'metaTitles',
            'metaDescription' => 'metaDescriptions',
            'metaKeyword' => 'metaKeywords'
        ];

        $this->assertEquals(
            [
                new DefaultFallbackExtensionPass([
                    Product::class => $fields,
                    Category::class => $fields,
                    Page::class => $fields,
                    Brand::class => $fields,
                ]),
                new ContentNodeFieldsChangesCompilerPass(
                    array_values($fields),
                    'oro_product.event_listener.product_content_variant_reindex'
                ),
                new ContentNodeFieldsChangesCompilerPass(
                    array_values($fields),
                    'oro_catalog.event_listener.category_content_variant_index'
                ),
                new UrlItemsProviderCompilerPass(
                    'oro_seo.sitemap.provider.url_items_provider_registry',
                    'oro_seo.sitemap.url_items_provider'
                ),
                new UrlItemsProviderCompilerPass(
                    'oro_seo.sitemap.provider.website_access_denied_urls_provider_registry',
                    'oro_seo.sitemap.website_access_denied_urls_provider'
                ),
                new FullListUrlProvidersCompilerPass(),
                new MigrateFileStorageCommandCompilerPass()
            ],
            $passes
        );
    }
}
