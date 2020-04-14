<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider\RelatedItem;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItem\RelatedItemDataProviderCacheDecorator;
use Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItem\RelatedItemDataProviderInterface;

class RelatedItemDataProviderCacheDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var RelatedItemDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dataProvider;

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var RelatedItemDataProviderCacheDecorator */
    private $decorator;

    protected function setUp(): void
    {
        $this->dataProvider = $this->createMock(RelatedItemDataProviderInterface::class);
        $this->cache = $this->createMock(Cache::class);
        $this->decorator = new RelatedItemDataProviderCacheDecorator(
            $this->dataProvider,
            $this->cache,
            'cache_key_%d'
        );
    }

    public function testFetchRelatedItemsFromCacheIfCacheContainsData()
    {
        $product = $this->createMock(Product::class);
        $relatedProducts = [$this->createMock(Product::class)];

        $product->expects(self::once())
            ->method('getId')
            ->willReturn(123);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('cache_key_123')
            ->willReturn($relatedProducts);
        $this->cache->expects($this->never())
            ->method('save');
        $this->dataProvider->expects($this->never())
            ->method('getRelatedItems');

        $this->assertSame($relatedProducts, $this->decorator->getRelatedItems($product));
    }

    public function testFetchRelatedItemsFromDataProviderAndSavesInCacheIfCacheDoesNotContainData()
    {
        $product = $this->createMock(Product::class);
        $relatedProducts = [$this->createMock(Product::class)];

        $product->expects(self::once())
            ->method('getId')
            ->willReturn(123);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('cache_key_123')
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('cache_key_123', $relatedProducts);
        $this->dataProvider->expects($this->once())
            ->method('getRelatedItems')
            ->with($this->identicalTo($product))
            ->willReturn($relatedProducts);

        $this->assertSame($relatedProducts, $this->decorator->getRelatedItems($product));
    }
}
