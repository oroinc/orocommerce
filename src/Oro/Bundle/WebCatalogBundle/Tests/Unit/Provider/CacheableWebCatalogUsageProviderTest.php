<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\WebCatalogBundle\Provider\CacheableWebCatalogUsageProvider;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;

class CacheableWebCatalogUsageProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|WebCatalogUsageProviderInterface */
    private $provider;

    /** @var CacheableWebCatalogUsageProvider */
    private $cacheableProvider;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(WebCatalogUsageProviderInterface::class);

        $this->cacheableProvider = new CacheableWebCatalogUsageProvider($this->provider);
    }

    public function testIsInUse()
    {
        $webCatalog = $this->createMock(WebCatalogInterface::class);
        $webCatalog->expects(self::any())
            ->method('getId')
            ->willReturn(123);

        $this->provider->expects(self::once())
            ->method('isInUse')
            ->with(self::identicalTo($webCatalog))
            ->willReturn(true);

        self::assertTrue($this->cacheableProvider->isInUse($webCatalog));
        // test the result is cached
        self::assertTrue($this->cacheableProvider->isInUse($webCatalog));
    }

    public function testGetAssignedWebCatalogs()
    {
        $assignedWebCatalogs = [1 => 2];

        $this->provider->expects(self::once())
            ->method('getAssignedWebCatalogs')
            ->willReturn($assignedWebCatalogs);

        self::assertEquals($assignedWebCatalogs, $this->cacheableProvider->getAssignedWebCatalogs());
        // test the result is cached
        self::assertEquals($assignedWebCatalogs, $this->cacheableProvider->getAssignedWebCatalogs());
    }

    public function testHasCacheAndClearCacheForInUse()
    {
        $webCatalog = $this->createMock(WebCatalogInterface::class);
        $webCatalog->expects(self::any())
            ->method('getId')
            ->willReturn(123);
        $this->provider->expects(self::any())
            ->method('isInUse')
            ->with(self::identicalTo($webCatalog))
            ->willReturn(true);

        self::assertFalse($this->cacheableProvider->hasCache());

        $this->cacheableProvider->isInUse($webCatalog);
        self::assertTrue($this->cacheableProvider->hasCache());

        $this->cacheableProvider->clearCache();
        self::assertFalse($this->cacheableProvider->hasCache());
    }

    public function testHasCacheAndClearCacheForAssignedWebCatalogs()
    {
        $this->provider->expects(self::once())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 2]);

        self::assertFalse($this->cacheableProvider->hasCache());

        $this->cacheableProvider->getAssignedWebCatalogs();
        self::assertTrue($this->cacheableProvider->hasCache());

        $this->cacheableProvider->clearCache();
        self::assertFalse($this->cacheableProvider->hasCache());
    }
}
