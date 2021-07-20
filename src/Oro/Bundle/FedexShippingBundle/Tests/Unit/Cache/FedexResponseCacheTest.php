<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCache;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKey;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use PHPUnit\Framework\TestCase;

class FedexResponseCacheTest extends TestCase
{
    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var FedexResponseCache
     */
    private $fedexCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheProvider::class);

        $this->fedexCache = new FedexResponseCache($this->cache);
    }

    public function testHas()
    {
        $this->assertNamespaceIsSet();
        $key = $this->createCacheKey();

        $this->cache
            ->expects(static::once())
            ->method('contains')
            ->with($key->getCacheKey())
            ->willReturn(true);

        static::assertTrue($this->fedexCache->has($key));
    }

    public function testGetNoResponse()
    {
        $this->assertNamespaceIsSet();
        $key = $this->createCacheKey();

        $this->cache
            ->expects(static::once())
            ->method('fetch')
            ->with($key->getCacheKey())
            ->willReturn(false);

        static::assertNull($this->fedexCache->get($key));
    }

    public function testGet()
    {
        $this->assertNamespaceIsSet();
        $key = $this->createCacheKey();
        $response = $this->createMock(FedexRateServiceResponseInterface::class);

        $this->cache
            ->expects(static::once())
            ->method('fetch')
            ->with($key->getCacheKey())
            ->willReturn($response);

        static::assertSame($response, $this->fedexCache->get($key));
    }

    public function testSetInvalidateAtIsSetInSettings()
    {
        $this->assertNamespaceIsSet();
        $key = $this->createCacheKey(new \DateTime('now +1 day'));
        $response = $this->createMock(FedexRateServiceResponseInterface::class);

        $this->cache
            ->expects(static::once())
            ->method('save')
            ->willReturn(true);

        static::assertTrue($this->fedexCache->set($key, $response));
    }

    public function testSetInvalidateAtNotSetInSettings()
    {
        $this->assertNamespaceIsSet();
        $key = $this->createCacheKey();
        $response = $this->createMock(FedexRateServiceResponseInterface::class);

        $this->cache
            ->expects(static::once())
            ->method('save')
            ->with($key->getCacheKey(), $response, 86400)
            ->willReturn(true);

        static::assertTrue($this->fedexCache->set($key, $response));
    }

    public function testDeleteNoKeyExist()
    {
        $this->assertNamespaceIsSet();
        $key = $this->createCacheKey();

        $this->cache
            ->expects(static::once())
            ->method('contains')
            ->with($key->getCacheKey())
            ->willReturn(false);

        static::assertFalse($this->fedexCache->delete($key));
    }

    public function testDelete()
    {
        $this->assertNamespaceIsSet();
        $key = $this->createCacheKey();

        $this->cache
            ->expects(static::once())
            ->method('contains')
            ->with($key->getCacheKey())
            ->willReturn(true);

        $this->cache
            ->expects(static::once())
            ->method('delete')
            ->with($key->getCacheKey())
            ->willReturn(true);

        static::assertTrue($this->fedexCache->delete($key));
    }

    public function testDeleteAll()
    {
        $this->assertNamespaceIsSet();

        $this->cache
            ->expects(static::once())
            ->method('deleteAll')
            ->willReturn(true);

        static::assertTrue($this->fedexCache->deleteAll($this->createMock(FedexIntegrationSettings::class)));
    }

    private function assertNamespaceIsSet()
    {
        $this->cache
            ->expects(static::any())
            ->method('setNamespace')
            ->with('oro_fedex_shipping_price');
    }

    private function createCacheKey(\DateTime $invalidateAt = null): FedexResponseCacheKey
    {
        return new FedexResponseCacheKey(
            new FedexRequest(),
            (new FedexIntegrationSettings())->setInvalidateCacheAt($invalidateAt)
        );
    }
}
