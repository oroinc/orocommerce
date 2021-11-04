<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FileCache;
use Oro\Bundle\RedirectBundle\Cache\UrlDataStorage;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UrlStorageCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FileCache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $persistentCache;

    /**
     * @var Cache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localCache;

    /**
     * @var Filesystem|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filesystem;

    /**
     * @var UrlStorageCache
     */
    private $storageCache;

    protected function setUp(): void
    {
        $this->persistentCache = $this->getMockBuilder(FileCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localCache = $this->createMock(Cache::class);
        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storageCache = new UrlStorageCache($this->persistentCache, $this->localCache, $this->filesystem, 1);
    }

    public function testDeleteAllNonClearableLocal()
    {
        $this->localCache->expects($this->never())
            ->method($this->anything());

        $this->persistentCache->expects($this->once())
            ->method('getDirectory')
            ->willReturn('/a');
        $this->persistentCache->expects($this->once())
            ->method('getNamespace')
            ->willReturn('b');

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with('/a' . DIRECTORY_SEPARATOR . 'b');

        $this->storageCache->deleteAll();
    }

    public function testDeleteAllNonClearableLocalWithNonFsPersistent()
    {
        $this->localCache->expects($this->never())
            ->method($this->anything());

        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $persistentCache */
        $persistentCache = $this->createMock(ArrayCache::class);
        $this->persistentCache->expects($this->once())
            ->method('deleteAll');
        $storageCache = new UrlStorageCache($persistentCache, $this->persistentCache, $this->filesystem);
        $storageCache->deleteAll();
    }

    public function testDeleteAllLocal()
    {
        /** @var ArrayCache|\PHPUnit\Framework\MockObject\MockObject $localCache */
        $localCache = $this->getMockBuilder(ArrayCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $localCache->expects($this->once())
            ->method('deleteAll');

        $this->persistentCache->expects($this->once())
            ->method('getDirectory')
            ->willReturn('/c');
        $this->persistentCache->expects($this->once())
            ->method('getNamespace')
            ->willReturn('d');

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with('/c' . DIRECTORY_SEPARATOR . 'd');

        $storageCache = new UrlStorageCache($this->persistentCache, $localCache, $this->filesystem);
        $storageCache->deleteAll();
    }

    public function testHasInLocalCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';

        $this->localCache->expects($this->once())
            ->method('contains')
            ->with($key)
            ->willReturn(true);
        $this->persistentCache->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->storageCache->has($routeName, $routeParameters));
    }

    public function testHasInPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';

        $this->localCache->expects($this->once())
            ->method('contains')
            ->with($key)
            ->willReturn(false);

        $this->persistentCache->expects($this->once())
            ->method('contains')
            ->with($key)
            ->willReturn(true);

        $this->assertTrue($this->storageCache->has($routeName, $routeParameters));
    }

    public function testGetUrlDataStorageCreateNew()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';

        $this->assertLocalCacheNotContainsValue($key);

        $this->persistentCache->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn(false);

        $this->assertFalse($this->storageCache->getUrl($routeName, $routeParameters));
    }

    public function testGetUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';
        $localizationId = 1;

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('getUrl')
            ->with($routeParameters, $localizationId)
            ->willReturn($url);

        $this->assertEquals($url, $this->storageCache->getUrl($routeName, $routeParameters, $localizationId));
    }

    public function testRemoveUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $localizationId = 1;

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('removeUrl')
            ->with($routeParameters, $localizationId);

        $this->storageCache->removeUrl($routeName, $routeParameters, $localizationId);
    }

    public function testGetSlug()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $slug = 'test';
        $localizationId = 1;

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('getSlug')
            ->with($routeParameters, $localizationId)
            ->willReturn($slug);

        $this->assertEquals($slug, $this->storageCache->getSlug($routeName, $routeParameters, $localizationId));
    }

    public function testSetUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';
        $localizationId = 1;

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('setUrl')
            ->with($routeParameters, $url, $localizationId);

        $this->storageCache->setUrl($routeName, $routeParameters, $url, $localizationId);
    }

    public function testFlushExistsInPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $oldStorage */
        $oldStorage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('setUrl')
            ->with($routeParameters, $url);

        $this->storageCache->setUrl($routeName, $routeParameters, $url);

        $this->persistentCache->expects($this->once())
            ->method('contains')
            ->with($key)
            ->willReturn(true);
        $this->persistentCache->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn($oldStorage);
        $oldStorage->expects($this->once())
            ->method('merge')
            ->with($storage);

        $this->persistentCache->expects($this->once())
            ->method('save')
            ->with($key, $oldStorage);

        $this->storageCache->flushAll();
    }

    public function testFlushNotInPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('setUrl')
            ->with($routeParameters, $url);

        $this->storageCache->setUrl($routeName, $routeParameters, $url);

        $this->persistentCache->expects($this->once())
            ->method('contains')
            ->with($key)
            ->willReturn(false);
        $this->persistentCache->expects($this->never())
            ->method('fetch')
            ->with($key);

        $this->persistentCache->expects($this->once())
            ->method('save')
            ->with($key, $storage);

        $this->storageCache->flushAll();
    }

    /**
     * @param string $key
     * @param UrlDataStorage $storage
     */
    private function configureLocalCacheWithValue($key, $storage)
    {
        $this->localCache->expects($this->any())
            ->method('contains')
            ->with($key)
            ->willReturn(true);
        $this->localCache->expects($this->any())
            ->method('fetch')
            ->with($key)
            ->willReturn($storage);
    }

    /**
     * @param string $key
     */
    private function assertLocalCacheNotContainsValue($key)
    {
        $this->localCache->expects($this->once())
            ->method('save')
            ->with($key, $this->isInstanceOf(UrlDataStorage::class));
        $this->localCache->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn(false);
    }
}
