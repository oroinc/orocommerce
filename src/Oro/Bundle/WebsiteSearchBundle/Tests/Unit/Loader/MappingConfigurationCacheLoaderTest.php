<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Loader;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\WebsiteSearchBundle\Cache\MappingConfigurationCacheProvider;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;
use Oro\Bundle\WebsiteSearchBundle\Loader\MappingConfigurationCacheLoader;
use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;
use Oro\Component\Config\CumulativeResourceInfo;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MappingConfigurationCacheLoaderTest extends \PHPUnit\Framework\TestCase
{
    const GET_HASH_RESULT = 'some_hash';

    /**
     * @var array
     */
    private static $configuration = [
        'Oro\Bundle\TestPageBundle\Entity\Page' => [
            'alias' => 'page_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'title_LOCALIZATION_ID',
                    'type' => 'text',
                ],
            ],
        ],
    ];

    /**
     * @var ResourcesHashProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resourceHashProvider;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheProvider;

    /**
     * @var MappingConfigurationCacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mappingCacheProvider;

    /**
     * @var MappingConfigurationCacheLoader
     */
    private $configurationCacheLoader;

    /**
     * @var ConfigurationLoaderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configurationLoader;

    protected function setUp()
    {
        $this->resourceHashProvider = $this->createMock(ResourcesHashProvider::class);
        $this->cacheProvider = $this->createMock(CacheProvider::class);
        $this->mappingCacheProvider = $this->createMock(MappingConfigurationCacheProvider::class);

        $this->configurationLoader = $this->createMock(ConfigurationLoaderInterface::class);
        $this->configurationLoader
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn(self::$configuration);
    }

    protected function tearDown()
    {
        unset(
            $this->configurationCacheLoader,
            $this->cacheProvider,
            $this->configurationLoader,
            $this->resourceHashProvider
        );
    }

    public function testGetConfigurationWhenDebugIsOffAndNoCacheExists()
    {
        $this->initConfigurationCacheLoader(false);
        $this->configureResourcesAndHash();

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->with(MappingConfigurationCacheLoader::CACHE_KEY_HASH)
            ->willReturn(false);

        $this->assertEquals(self::$configuration, $this->configurationCacheLoader->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOffAndCacheExists()
    {
        $this->initConfigurationCacheLoader(false);

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnMap([
                [MappingConfigurationCacheLoader::CACHE_KEY_HASH, self::GET_HASH_RESULT],
                [MappingConfigurationCacheLoader::CACHE_KEY_CONFIGURATION, self::$configuration]
            ]);
        $this->cacheProvider
            ->expects($this->never())
            ->method('saveMultiple');

        $this->assertEquals(self::$configuration, $this->configurationCacheLoader->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOnAndNoCacheExists()
    {
        $this->initConfigurationCacheLoader(true);
        $this->configureResourcesAndHash();

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->with(MappingConfigurationCacheLoader::CACHE_KEY_HASH)
            ->willReturn(false);

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                MappingConfigurationCacheLoader::CACHE_KEY_HASH => self::GET_HASH_RESULT,
                MappingConfigurationCacheLoader::CACHE_KEY_CONFIGURATION => self::$configuration,
            ]);

        $this->assertEquals(self::$configuration, $this->configurationCacheLoader->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOnAndCacheExistsAndHashNotMatchStoredHash()
    {
        $this->initConfigurationCacheLoader(true);

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->with(MappingConfigurationCacheLoader::CACHE_KEY_HASH)
            ->willReturn('another_hash');

        $resources = [
            new CumulativeResourceInfo('bundleName', 'name', 'path', ['data' => 'value']),
            new CumulativeResourceInfo('bundleName', 'name', 'path', ['data' => 'value']),
        ];

        $this->configurationLoader
            ->expects($this->once())
            ->method('getResources')
            ->willReturn($resources);

        $this->resourceHashProvider
            ->expects($this->exactly(2))
            ->method('getHash')
            ->with($resources)
            ->willReturn(self::GET_HASH_RESULT);

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                MappingConfigurationCacheLoader::CACHE_KEY_HASH => self::GET_HASH_RESULT,
                MappingConfigurationCacheLoader::CACHE_KEY_CONFIGURATION => self::$configuration,
            ]);

        $this->assertEquals(self::$configuration, $this->configurationCacheLoader->getConfiguration());
    }

    public function testGetConfigurationDataWhenDebugIsOnAndCacheExistsAndHashMatches()
    {
        $this->initConfigurationCacheLoader(true);
        $this->configureResourcesAndHash();

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [MappingConfigurationCacheLoader::CACHE_KEY_HASH],
                [MappingConfigurationCacheLoader::CACHE_KEY_CONFIGURATION]
            )
            ->willReturnOnConsecutiveCalls(
                self::GET_HASH_RESULT,
                self::$configuration
            );

        $this->assertEquals(self::$configuration, $this->configurationCacheLoader->getConfiguration());
    }

    public function testClearCache()
    {
        $this->initConfigurationCacheLoader(false);

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                [MappingConfigurationCacheLoader::CACHE_KEY_HASH],
                [MappingConfigurationCacheLoader::CACHE_KEY_CONFIGURATION]
            );

        $this->configurationCacheLoader->clearCache();
    }

    /**
     * @dataProvider warmUpCacheWhenNoCachedHashProvider
     */
    public function testWarmUpCacheWhenNoCachedHash($debug)
    {
        $this->initConfigurationCacheLoader($debug);
        $this->configureResourcesAndHash();

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->with(MappingConfigurationCacheLoader::CACHE_KEY_HASH)
            ->willReturn(false);
        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                [MappingConfigurationCacheLoader::CACHE_KEY_HASH],
                [MappingConfigurationCacheLoader::CACHE_KEY_CONFIGURATION]
            );
        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                MappingConfigurationCacheLoader::CACHE_KEY_HASH => self::GET_HASH_RESULT,
                MappingConfigurationCacheLoader::CACHE_KEY_CONFIGURATION => self::$configuration,
            ]);

        $this->configurationCacheLoader->warmUpCache();
    }

    public function warmUpCacheWhenNoCachedHashProvider()
    {
        return [
            ['debug' => false],
            ['debug' => true]
        ];
    }

    public function testGetConfigurationLocalCache()
    {
        $this->initConfigurationCacheLoader(true);
        $this->configureResourcesAndHash();

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [MappingConfigurationCacheLoader::CACHE_KEY_HASH],
                [MappingConfigurationCacheLoader::CACHE_KEY_CONFIGURATION]
            )
            ->willReturnOnConsecutiveCalls(
                self::GET_HASH_RESULT,
                []
            );

        $this->configurationCacheLoader->getConfiguration();
        $this->configurationCacheLoader->getConfiguration();
    }

    public function testGetResources()
    {
        $this->initConfigurationCacheLoader(true);

        $this->configurationLoader
            ->expects($this->once())
            ->method('getResources')
            ->willReturn([]);

        $this->assertEquals([], $this->configurationCacheLoader->getResources());
        $this->assertEquals([], $this->configurationCacheLoader->getResources());
    }

    public function testGetConfigurationFallback()
    {
        $this->initConfigurationCacheLoader(false);
        $this->configurationCacheLoader->setMappingConfigurationCacheProvider($this->mappingCacheProvider);
        $this->configureResourcesAndHash();

        $this->mappingCacheProvider
            ->expects(self::once())
            ->method('fetchConfiguration')
            ->willReturn([false, []]);

        self::assertEquals(self::$configuration, $this->configurationCacheLoader->getConfiguration());
    }

    public function testClearCacheFallback()
    {
        $this->initConfigurationCacheLoader(false);
        $this->configurationCacheLoader->setMappingConfigurationCacheProvider($this->mappingCacheProvider);

        $this->mappingCacheProvider
            ->expects(self::once())
            ->method('deleteConfiguration');

        $this->configurationCacheLoader->clearCache();
    }

    public function testWarmUpCacheFallback()
    {
        $this->initConfigurationCacheLoader(false);
        $this->configurationCacheLoader->setMappingConfigurationCacheProvider($this->mappingCacheProvider);
        $this->configureResourcesAndHash();

        $this->mappingCacheProvider
            ->expects(self::once())
            ->method('deleteConfiguration');

        $this->mappingCacheProvider
            ->expects(self::once())
            ->method('fetchConfiguration')
            ->willReturn([false, []]);

        $this->configurationCacheLoader->warmUpCache();
    }

    /**
     * @param bool $debug
     */
    private function initConfigurationCacheLoader($debug)
    {
        $this->configurationCacheLoader = new MappingConfigurationCacheLoader(
            $this->cacheProvider,
            $this->configurationLoader,
            $this->resourceHashProvider,
            $debug
        );
    }

    private function configureResourcesAndHash($getHashCallCount = 1)
    {
        $resources = [
            new CumulativeResourceInfo('bundleName', 'name', 'path', ['data' => 'value']),
            new CumulativeResourceInfo('bundleName', 'name', 'path', ['data' => 'value']),
        ];

        $this->configurationLoader
            ->expects($this->once())
            ->method('getResources')
            ->willReturn($resources);

        if (0 === $getHashCallCount) {
            $this->resourceHashProvider
                ->expects($this->never())
                ->method('getHash');
        } else {
            $this->resourceHashProvider
                ->expects($this->exactly($getHashCallCount))
                ->method('getHash')
                ->with($resources)
                ->willReturn(self::GET_HASH_RESULT);
        }
    }
}
