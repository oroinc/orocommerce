<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingPriceCacheTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @internal
     */
    const PROCESSING_TIME_ERROR_VALUE = 3;

    /**
     * @var ShippingPriceCache
     */
    protected $cache;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheProvider;

    public function setUp()
    {
        $this->cacheProvider = $this->getMockBuilder(CacheProvider::class)
            ->setMethods(['fetch', 'contains', 'save', 'deleteAll'])->getMockForAbstractClass();

        $this->cache = new ShippingPriceCache($this->cacheProvider);
    }

    public function testFetchPrice()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $upsSettings = $this->getUPSSettingsMock($invalidateCacheAt);

        $key = $this->cache->createKey($upsSettings, new PriceRequest(), 'method_id', 'type_id');

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(true);

        $price = Price::create(10, 'USD');

        $this->cacheProvider->expects(static::once())
            ->method('fetch')
            ->with($stringKey)
            ->willReturn($price);

        static::assertSame($price, $this->cache->fetchPrice($key));
    }

    public function testFetchPriceFalse()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $upsSettings = $this->getUPSSettingsMock($invalidateCacheAt);

        $key = $this->cache->createKey($upsSettings, new PriceRequest(), 'method_id', 'type_id');

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(false);

        $this->cacheProvider->expects(static::never())
            ->method('fetch');

        static::assertFalse($this->cache->fetchPrice($key));
    }

    public function testContainsPrice()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $upsSettings = $this->getUPSSettingsMock($invalidateCacheAt);

        $key = $this->cache->createKey($upsSettings, new PriceRequest(), 'method_id', 'type_id');

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(true);

        static::assertTrue($this->cache->containsPrice($key));
    }

    public function testContainsPriceFalse()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $upsSettings = $this->getUPSSettingsMock($invalidateCacheAt);

        $key = $this->cache->createKey($upsSettings, new PriceRequest(), 'method_id', 'type_id');

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(false);

        static::assertFalse($this->cache->containsPrice($key));
    }

    /**
     * @dataProvider savePriceDataProvider
     *
     * @param string $invalidateCacheAtString
     * @param string $expectedLifetime
     */
    public function testSavePrice($invalidateCacheAtString, $expectedLifetime)
    {
        $invalidateCacheAt = new \DateTime($invalidateCacheAtString);

        $upsSettings = $this->getUPSSettingsMock($invalidateCacheAt);

        $key = $this->cache->createKey($upsSettings, new PriceRequest(), 'method_id', 'type_id');

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $price = Price::create(10, 'USD');
        $this->cacheProvider->expects(static::once())
            ->method('save')
            ->with($stringKey, $price)
            ->will($this->returnCallback(function ($actualKey, $price, $actualLifetime) use ($expectedLifetime) {
                static::assertLessThan(self::PROCESSING_TIME_ERROR_VALUE, abs($expectedLifetime - $actualLifetime));
            }));

        static::assertEquals($this->cache, $this->cache->savePrice($key, $price));
    }

    /**
     * @return array
     */
    public function savePriceDataProvider()
    {
        return [
            'earlier than lifetime' => [
                'invalidateCacheAt' => '+3second',
                'expectedLifetime' => 3,
            ],
            'in past' => [
                'invalidateCacheAt' => '-1second',
                'expectedLifetime' => ShippingPriceCache::LIFETIME,
            ],
            'later than lifetime' => [
                'invalidateCacheAt' => '+24hour+10second',
                'expectedLifetime' => ShippingPriceCache::LIFETIME + 10,
            ],
        ];
    }

    /**
     * @param \DateTime $invalidateCacheAt
     *
     * @return UPSTransport|object
     */
    private function getUPSSettingsMock(\DateTime $invalidateCacheAt): UPSTransport
    {
        return $this->getEntity(UPSTransport::class, [
            'upsInvalidateCacheAt' => $invalidateCacheAt,
        ]);
    }
}
