<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;
use Oro\Component\Testing\Unit\EntityTrait;

class ZipCodeRulesCacheTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ZipCodeRulesCache
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

        $this->cache = new ZipCodeRulesCache($this->cacheProvider);
    }

    public function testContainsZipCodeRules()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');
        $key = $this->cache->createKey($this->getEntity(DPDTransport::class, [
            'invalidateCacheAt' => $invalidateCacheAt,
        ]), new ZipCodeRulesRequest(), 'method_id');
        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(true);

        static::assertTrue($this->cache->containsZipCodeRules($key));
    }

    public function testContainsZipCodeRulesFalse()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');
        $key = $this->cache->createKey($this->getEntity(DPDTransport::class, [
            'invalidateCacheAt' => $invalidateCacheAt,
        ]), new ZipCodeRulesRequest(), 'method_id');
        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(false);

        static::assertFalse($this->cache->containsZipCodeRules($key));
    }

    public function testFetchPrice()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');
        $key = $this->cache->createKey(
            $this->getEntity(DPDTransport::class, ['invalidateCacheAt' => $invalidateCacheAt]),
            new ZipCodeRulesRequest(),
            'method_id'
        );
        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(true);

        $json = '{
                   "Version": 100,
                   "Ack": true,
                   "Language": "en_EN",
                   "TimeStamp": "2017-01-06T14:22:32.6175888+01:00",
                   "ZipCodeRules": {
                      "Country": "a country",
                      "ZipCode": "zip code",
                      "NoPickupDays": "01.01.2017,18.04.2017,25.12.2017",
                      "ExpressCutOff": "12:00",
                      "ClassicCutOff": "08:00",
                      "PickupDepot": "0197",
                      "State": "a state"
                   }
                }';
        $jsonArr = json_decode($json, true);
        $zipCodeRulesResponse = new ZipCodeRulesResponse($jsonArr);

        $this->cacheProvider->expects(static::once())
            ->method('fetch')
            ->with($stringKey)
            ->willReturn($zipCodeRulesResponse);

        static::assertSame($zipCodeRulesResponse, $this->cache->fetchZipCodeRules($key));
    }

    public function testFetchPriceFalse()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');
        $key = $this->cache->createKey(
            $this->getEntity(DPDTransport::class, ['invalidateCacheAt' => $invalidateCacheAt]),
            new ZipCodeRulesRequest(),
            'method_id'
        );
        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(false);

        $this->cacheProvider->expects(static::never())
            ->method('fetch');

        static::assertFalse($this->cache->fetchZipCodeRules($key));
    }

    /**
     * @dataProvider saveZipCodeRulesDataProvider
     *
     * @param string $invalidateCacheAt
     * @param string $expectedLifetime
     */
    public function testSaveZipCodeRules($invalidateCacheAt, $expectedLifetime)
    {
        $invalidateCacheAt = new \DateTime($invalidateCacheAt);
        $key = $this->cache->createKey($this->getEntity(DPDTransport::class, [
            'invalidateCacheAt' => $invalidateCacheAt,
        ]), new ZipCodeRulesRequest(), 'method_id');
        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $json = '{
                   "Version": 100,
                   "Ack": true,
                   "Language": "en_EN",
                   "TimeStamp": "2017-01-06T14:22:32.6175888+01:00",
                   "ZipCodeRules": {
                      "Country": "a country",
                      "ZipCode": "zip code",
                      "NoPickupDays": "01.01.2017,18.04.2017,25.12.2017",
                      "ExpressCutOff": "12:00",
                      "ClassicCutOff": "08:00",
                      "PickupDepot": "0197",
                      "State": "a state"
                   }
                }';
        $jsonArr = json_decode($json, true);
        $zipCodeRulesResponse = new ZipCodeRulesResponse($jsonArr);

        $this->cacheProvider->expects(static::once())
            ->method('save')
            ->with($stringKey, $zipCodeRulesResponse, $expectedLifetime);

        static::assertEquals($this->cache, $this->cache->saveZipCodeRules($key, $zipCodeRulesResponse));
    }

    /**
     * @return array
     */
    public function saveZipCodeRulesDataProvider()
    {
        return [
            'earlier than lifetime' => [
                'invalidateCacheAt' => '+1second',
                'expectedLifetime' => 1,
            ],
            'in past' => [
                'invalidateCacheAt' => '-1second',
                'expectedLifetime' => ZipCodeRulesCache::LIFETIME,
            ],
            'later than lifetime' => [
                'invalidateCacheAt' => '+24hour+10second',
                'expectedLifetime' => ZipCodeRulesCache::LIFETIME + 10,
            ],
        ];
    }
}
