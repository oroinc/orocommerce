<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Cache;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextCacheKeyGenerator;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class ShippingPriceCacheTest extends TestCase
{
    use EntityTrait;

    private CacheItemPoolInterface|MockObject $cacheProvider;

    private CacheItemInterface|MockObject $cacheItem;

    private ShippingPriceCache $cache;

    protected function setUp(): void
    {
        $this->cacheProvider = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);

        $keyGenerator = $this->createMock(ShippingContextCacheKeyGenerator::class);
        $keyGenerator->expects(self::any())
            ->method('generateKey')
            ->willReturnCallback(function (ShippingContextInterface $context) {
                return ($context->getSourceEntity() ? get_class($context->getSourceEntity()) : '')
                    . '_' . $context->getSourceEntityIdentifier();
            });

        $this->cache = new ShippingPriceCache($this->cacheProvider, $keyGenerator);
    }

    /**
     * @dataProvider hasPriceDataProvider
     */
    public function testHasPrice(bool $isContains, bool $hasPrice): void
    {
        $context = $this->createShippingContext([]);
        $this->cacheProvider->expects(self::once())
            ->method('getItem')
            ->with('_|flat_rate|primary|11')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn($isContains);

        self::assertEquals($hasPrice, $this->cache->hasPrice($context, 'flat_rate', 'primary', 11));
    }

    public function hasPriceDataProvider(): array
    {
        return [
            [
                'isContains' => true,
                'hasPrice' => true,
            ],
            [
                'isContains' => false,
                'hasPrice' => false,
            ]
        ];
    }

    /**
     * @dataProvider getPriceDataProvider
     */
    public function testGetPrice(bool $isContains, Price $price = null): void
    {
        $context = $this->createShippingContext([]);

        $this->cacheProvider->expects(self::once())
            ->method('getItem')
            ->with('_|flat_rate|primary|222')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn($isContains);
        $this->cacheItem->expects(self::any())
            ->method('get')
            ->willReturn($isContains ? $price : null);

        self::assertSame($price, $this->cache->getPrice($context, 'flat_rate', 'primary', 222));
    }

    public function getPriceDataProvider(): array
    {
        return [
            [
                'isContains' => true,
                'price' => Price::create(5, 'USD'),
            ],
            [
                'isContains' => false,
                'price' => null,
            ]
        ];
    }

    public function testSavePrice(): void
    {
        $context = $this->createShippingContext([
            ShippingContext::FIELD_SOURCE_ENTITY => new \stdClass(),
            ShippingContext::FIELD_SOURCE_ENTITY_ID => 1
        ]);

        $price = Price::create(10, 'USD');
        $this->cacheProvider->expects($this->once())
            ->method('getItem')
            ->with('stdClass_1|flat_rate|primary|333')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($price)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('expiresAfter')
            ->with(3600)
            ->willReturn($this->cacheItem);
        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with($this->cacheItem);

        $this->cache->savePrice($context, 'flat_rate', 'primary', 333, $price);
    }

    public function testDeleteAllPrices(): void
    {
        $this->cacheProvider->expects(self::once())
            ->method('clear');

        $this->cache->deleteAllPrices();
    }

    private function createShippingContext(array $params): ShippingContext
    {
        $actualParams = array_merge([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection([])
        ], $params);

        return new ShippingContext($actualParams);
    }
}
