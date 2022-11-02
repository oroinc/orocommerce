<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Visibility\Cache\Product;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\CacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

class CacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * @var CacheBuilder
     */
    protected $cacheBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheBuilder = new CacheBuilder();

        foreach ($this->builders as $builder) {
            $this->cacheBuilder->addBuilder($builder);
        }
    }

    /**
     * @dataProvider productCategoryChangedDataProvider
     */
    public function testProductCategoryChanged(bool $scheduleReindex): void
    {
        $product = new Product();

        /** @var \PHPUnit\Framework\MockObject\MockObject|ProductCaseCacheBuilderInterface $customBuilder */
        $customBuilder
            = $this->createMock(ProductCaseCacheBuilderInterface::class);
        $customBuilder->expects($this->once())
            ->method('productCategoryChanged')
            ->with($product, $scheduleReindex);

        $this->cacheBuilder->addBuilder($customBuilder);
        $this->cacheBuilder->productCategoryChanged($product, $scheduleReindex);
    }

    public function productCategoryChangedDataProvider(): array
    {
        return [
            [
                'scheduleReindex' => false,
            ],
            [
                'scheduleReindex' => true,
            ],
        ];
    }
}
