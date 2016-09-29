<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit;

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

        $bundle = new OroSEOBundle($this->getMock(KernelInterface::class));
        $bundle->build($container);

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
                ]),
            ],
            $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses()
        );
    }
}
