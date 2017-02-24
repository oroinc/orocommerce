<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit;

use Oro\Bundle\SEOBundle\DependencyInjection\Compiler\UrlItemsProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

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
                new UrlItemsProviderCompilerPass(),
            ],
            $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses()
        );
    }
}
