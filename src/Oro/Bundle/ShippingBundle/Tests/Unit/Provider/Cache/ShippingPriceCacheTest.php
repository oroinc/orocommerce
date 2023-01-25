<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextCacheKeyGenerator;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingPriceCacheTest extends TestCase
{
    use EntityTrait;

    /** @var ShippingPriceCache */
    private $cache;

    /** @var CacheProvider|MockObject */
    private $cacheProvider;

    /** @var ShippingContextCacheKeyGenerator|MockObject */
    private $keyGenerator;

    protected function setUp(): void
    {
        $this->cacheProvider = $this->createMock(CacheProvider::class);

        $this->keyGenerator = $this->createMock(ShippingContextCacheKeyGenerator::class);
        $this->keyGenerator->expects(self::any())
            ->method('generateKey')
            ->willReturnCallback(function (ShippingContextInterface $context) {
                return ($context->getSourceEntity() ? get_class($context->getSourceEntity()) : '')
                    . '_' . $context->getSourceEntityIdentifier();
            });

        $this->cache = new ShippingPriceCache($this->cacheProvider, $this->keyGenerator);
    }

    /**
     * @dataProvider hasPriceDataProvider
     */
    public function testHasPrice(bool $isContains, bool $hasPrice): void
    {
        $context = $this->createShippingContext([]);

        $this->cacheProvider->expects(self::once())
            ->method('contains')
            ->with('_flat_rateprimary')
            ->willReturn($isContains);

        self::assertEquals($hasPrice, $this->cache->hasPrice($context, 'flat_rate', 'primary', 11));
    }

    /**
     * @dataProvider hasPriceDataProvider
     */
    public function testHasRulePrice(bool $isContains, bool $hasPrice): void
    {
        $context = $this->createShippingContext([]);

        $this->cacheProvider->expects(self::once())
            ->method('contains')
            ->with('_|flat_rate|primary|11')
            ->willReturn($isContains);

        self::assertEquals($hasPrice, $this->cache->hasRulePrice($context, 'flat_rate', 'primary', 11));
    }

    public function hasPriceDataProvider()
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

        $this->cacheProvider->expects(self::any())
            ->method('fetch')
            ->with('_flat_rateprimary')
            ->willReturn($isContains ? $price : false);

        self::assertSame($price, $this->cache->getPrice($context, 'flat_rate', 'primary', 222));
    }

    /**
     * @dataProvider getPriceDataProvider
     */
    public function testGetRulePrice(bool $isContains, Price $price = null): void
    {
        $context = $this->createShippingContext([]);

        $this->cacheProvider->expects(self::any())
            ->method('fetch')
            ->with('_|flat_rate|primary|222')
            ->willReturn($isContains ? $price : false);

        self::assertSame($price, $this->cache->getRulePrice($context, 'flat_rate', 'primary', 222));
    }

    public function getPriceDataProvider()
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

        $this->cacheProvider->expects(self::once())
            ->method('save')
            ->with('stdClass_1flat_rateprimary', $price, ShippingPriceCache::CACHE_LIFETIME)
            ->willReturn($price);

        $this->cache->savePrice($context, 'flat_rate', 'primary', $price);
    }

    public function testSaveRulePrice(): void
    {
        $context = $this->createShippingContext([
            ShippingContext::FIELD_SOURCE_ENTITY => new \stdClass(),
            ShippingContext::FIELD_SOURCE_ENTITY_ID => 1
        ]);

        $price = Price::create(10, 'USD');

        $this->cacheProvider->expects(self::once())
            ->method('save')
            ->with('stdClass_1|flat_rate|primary|333', $price, ShippingPriceCache::CACHE_LIFETIME)
            ->willReturn($price);

        $this->cache->saveRulePrice($context, 'flat_rate', 'primary', 333, $price);
    }

    public function testDeleteAllPrices(): void
    {
        $this->cacheProvider->expects(self::once())
            ->method('deleteAll');

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
