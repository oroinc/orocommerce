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

    protected function setUp()
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
        $this->cache->expects($this->once())->method('contains')->willReturn(true);
        $this->cache->expects($this->once())->method('fetch');

        $this->decorator->getRelatedItems(new Product());
    }

    public function testFetchRelatedItemsFromDataProviderAndSavesInCacheIfCacheDoesNotContainData()
    {
        $this->cache->expects($this->once())->method('contains')->willReturn(false);
        $this->cache->expects($this->never())->method('fetch');
        $this->cache->expects($this->once())->method('save');

        $expectedProducts = [new Product(), new Product()];
        $this->dataProvider->expects($this->once())->method('getRelatedItems')->willReturn($expectedProducts);

        $this->assertSame($expectedProducts, $this->decorator->getRelatedItems(new Product()));
    }
}
