<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Cache\UrlDataStorage;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlCacheAwareProvider;

class SluggableUrlCacheAwareProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SluggableUrlCacheAwareProvider */
    protected $testable;

    /** @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(UrlCacheInterface::class);

        $this->testable = new SluggableUrlCacheAwareProvider(
            $this->cache
        );
    }

    public function testGetUrlWithoutContext()
    {
        $this->testable->setContextUrl('');

        $name = 'oro_frontend_view';
        $params = ['id' => 10];

        $localizationId = 1;

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with($name, $params, $localizationId)
            ->willReturn('slug-url');

        $this->assertEquals('slug-url', $this->testable->getUrl($name, $params, $localizationId));
    }

    public function testGetUrlWithContext()
    {
        $this->testable->setContextUrl('some-context');

        $name = 'oro_frontend_view';
        $params = ['id' => 10];

        $localizationId = 1;

        $this->cache->expects($this->once())
            ->method('getSlug')
            ->with($name, $params, $localizationId)
            ->willReturn('slug-url-context');
        $this->cache->expects($this->never())
            ->method('getUrl');

        $this->assertEquals('slug-url-context', $this->testable->getUrl($name, $params, $localizationId));
    }

    public function testGetUrlNoStorage()
    {
        $this->testable->setContextUrl('');

        $name = 'oro_frontend_view';
        $params = ['id' => 10];

        $localizationId = 1;

        $storage = $this->createMock(UrlDataStorage::class);
        $storage->expects($this->never())
            ->method('getSlug');

        $this->cache->expects($this->once())
            ->method('getUrl')
            ->with($name, $params, $localizationId)
            ->willReturn(false);

        $this->assertEquals(null, $this->testable->getUrl($name, $params, $localizationId));
    }

    public function testGetUrlNoStorageWhenContextUrl(): void
    {
        $this->testable->setContextUrl('sample/url');

        $name = 'oro_frontend_view';
        $params = ['id' => 10];

        $localizationId = 1;

        $storage = $this->createMock(UrlDataStorage::class);
        $storage->expects($this->never())
            ->method('getUrl');

        $this->cache->expects($this->once())
            ->method('getSlug')
            ->with($name, $params, $localizationId)
            ->willReturn(false);

        $this->assertEquals(null, $this->testable->getUrl($name, $params, $localizationId));
    }
}
