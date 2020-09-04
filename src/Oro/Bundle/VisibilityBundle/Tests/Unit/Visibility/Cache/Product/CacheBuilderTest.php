<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Visibility\Cache\Product;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\CacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

class CacheBuilderTest extends AbstractCacheBuilderTest
{
    /** @var CacheBuilder */
    protected $cacheBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheBuilder = new CacheBuilder();

        foreach ($this->builders as $builder) {
            $this->cacheBuilder->addBuilder($builder);
        }
    }

    public function testProductCategoryChanged(): void
    {
        $product = new Product();

        $customBuilder = $this->createMock(ProductCaseCacheBuilderInterface::class);
        $customBuilder
            ->expects($this->once())
            ->method('productCategoryChanged')
            ->with($product);

        $this->cacheBuilder->addBuilder($customBuilder);

        $this->cacheBuilder->productCategoryChanged($product);
    }

    public function testProductsCategoryChangedWithDisabledReindex(): void
    {
        $product = new Product();

        $customBuilder = $this->getMockBuilder(ProductCaseCacheBuilderInterface::class)
            ->setMethods(
                [
                    'productCategoryChanged',
                    'toggleReindex',
                    'resolveVisibilitySettings',
                    'isVisibilitySettingsSupported',
                    'buildCache',
                ]
            )
            ->getMock();

        $customBuilder
            ->expects($this->exactly(2))
            ->method('toggleReindex')
            ->withConsecutive([false], [true]);

        $customBuilder
            ->expects($this->once())
            ->method('productCategoryChanged')
            ->with($product);

        $this->cacheBuilder->addBuilder($customBuilder);

        $this->cacheBuilder->productsCategoryChangedWithDisabledReindex([$product]);
    }
}
