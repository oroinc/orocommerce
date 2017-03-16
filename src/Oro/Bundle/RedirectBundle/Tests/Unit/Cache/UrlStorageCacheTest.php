<?php

namespace Oro\Bundle\RedirectBundle\Tests\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FileCache;
use Oro\Bundle\RedirectBundle\Cache\UrlDataStorage;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UrlStorageCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $persistentCache;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localCache;

    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystem;

    /**
     * @var UrlStorageCache
     */
    private $storageCache;

    protected function setUp()
    {
        $this->persistentCache = $this->getMockBuilder(FileCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localCache = $this->createMock(Cache::class);
        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storageCache = new UrlStorageCache($this->persistentCache, $this->localCache, $this->filesystem);
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

    public function testDeleteAllLocal()
    {
        /** @var ArrayCache|\PHPUnit_Framework_MockObject_MockObject $localCache */
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

    public function testGetCacheKey()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];

        $this->assertEquals(
            'test_2',
            UrlStorageCache::getCacheKey($routeName, $routeParameters)
        );
    }

    public function testGetUrlDataStorageFromLocalCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';

        $storage = new UrlDataStorage();

        $this->configureLocalCacheWithValue($key, $storage);
        $this->persistentCache->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($storage, $this->storageCache->getUrlDataStorage($routeName, $routeParameters));
    }

    public function testGetUrlDataStorageFromPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';

        $storage = new UrlDataStorage();
        $this->assertLocalCacheNotContainsValue($key, $storage);

        $this->persistentCache->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn($storage);

        $this->assertEquals($storage, $this->storageCache->getUrlDataStorage($routeName, $routeParameters));
    }

    public function testGetUrlDataStorageCreateNew()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';

        $storage = new UrlDataStorage();
        $this->assertLocalCacheNotContainsValue($key, $storage);

        $this->persistentCache->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn(false);

        $this->assertEquals($storage, $this->storageCache->getUrlDataStorage($routeName, $routeParameters));
    }

    public function testGetUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('getUrl')
            ->with($routeParameters)
            ->willReturn($url);

        $this->assertEquals($url, $this->storageCache->getUrl($routeName, $routeParameters));
    }

    public function testRemoveUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('removeUrl')
            ->with($routeParameters);

        $this->storageCache->removeUrl($routeName, $routeParameters);
    }

    public function testGetSlug()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $slug = 'test';

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('getSlug')
            ->with($routeParameters)
            ->willReturn($slug);

        $this->assertEquals($slug, $this->storageCache->getSlug($routeName, $routeParameters));
    }

    public function testSetUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('setUrl')
            ->with($routeParameters, $url);

        $this->storageCache->setUrl($routeName, $routeParameters, $url);
    }

    public function testFlushExistsInPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
         /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $oldStorage */
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

        $this->assertCacheUpdateAndFlush($key, $oldStorage);

        $this->storageCache->flush();
    }

    public function testFlushNotInPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
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

        $this->assertCacheUpdateAndFlush($key, $storage);

        $this->storageCache->flush();
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
     * @param UrlDataStorage $storage
     */
    private function assertLocalCacheNotContainsValue($key, UrlDataStorage $storage)
    {
        $this->localCache->expects($this->any())
            ->method('contains')
            ->with($key)
            ->willReturn(false);
        $this->localCache->expects($this->once())
            ->method('save')
            ->with($key, $this->isInstanceOf(UrlDataStorage::class));
        $this->localCache->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn($storage);
    }

    /**
     * @param $key
     * @param $oldStorage
     */
    private function assertCacheUpdateAndFlush($key, $oldStorage)
    {
        $this->persistentCache->expects($this->once())
            ->method('save')
            ->with($key, $oldStorage);
        $this->localCache->expects($this->once())
            ->method('delete')
            ->with($key);
    }
}
