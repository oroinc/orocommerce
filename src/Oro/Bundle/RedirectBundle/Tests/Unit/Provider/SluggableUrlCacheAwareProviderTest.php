<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Oro\Bundle\RedirectBundle\Cache\UrlDataStorage;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlCacheAwareProvider;

class SluggableUrlCacheAwareProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SluggableUrlCacheAwareProvider */
    protected $testable;

    /** @var UrlStorageCache|\PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    protected function setUp()
    {
        $this->cache = $this->createMock(UrlStorageCache::class);

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

        $storage = $this->createMock(UrlDataStorage::class);
        $storage->expects($this->never())
            ->method('getSlug');
        $storage->expects($this->once())
            ->method('getUrl')
            ->with($params, $localizationId)
            ->willReturn('slug-url');

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($name, $params)
            ->willReturn($storage);

        $this->assertEquals('slug-url', $this->testable->getUrl($name, $params, $localizationId));
    }

    public function testGetUrlWithContext()
    {
        $this->testable->setContextUrl('some-context');

        $name = 'oro_frontend_view';
        $params = ['id' => 10];

        $localizationId = 1;

        $storage = $this->createMock(UrlDataStorage::class);
        $storage->expects($this->once())
            ->method('getSlug')
            ->willReturn('slug-url-context');
        $storage->expects($this->never())
            ->method('getUrl');

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($name, $params)
            ->willReturn($storage);

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
        $storage->expects($this->never())
            ->method('getUrl');

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($name, $params)
            ->willReturn(null);

        $this->assertEquals(null, $this->testable->getUrl($name, $params, $localizationId));
    }
}
