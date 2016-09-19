<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Loader;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Component\Config\CumulativeResourceInfo;

use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;
use Oro\Bundle\WebsiteSearchBundle\Loader\MappingConfigurationCacheLoader;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;

class MappingConfigurationCacheLoaderTest extends \PHPUnit_Framework_TestCase
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
     * @var ResourcesHashProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceHashProvider;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheProvider;

    /**
     * @var MappingConfigurationCacheLoader
     */
    private $configurationCacheLoader;

    /**
     * @var ConfigurationLoaderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configurationLoader;

    protected function setUp()
    {
        $this->resourceHashProvider = $this->getMock(ResourcesHashProvider::class);
        $this->cacheProvider = $this->getMock(CacheProvider::class);

        $this->configurationLoader = $this->getMock(ConfigurationLoaderInterface::class);
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
        $this->setCacheExists(false);
        $this->initConfigurationCacheLoader(false);
        $this->configureResourcesAndHash();

        $this->assertEquals(self::$configuration, $this->configurationCacheLoader->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOffAndCacheExists()
    {
        $this->setCacheExists(true);
        $this->initConfigurationCacheLoader(false);

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->with(MappingConfigurationCacheLoader::CACHE_KEY_CONFIGURATION)
            ->willReturn(self::$configuration);

        $this->assertEquals(self::$configuration, $this->configurationCacheLoader->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOnAndNoCacheExists()
    {
        $this->setCacheExists(false);
        $this->initConfigurationCacheLoader(true);
        $this->configureResourcesAndHash();

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
        $this->setCacheExists(true);
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
        $this->setCacheExists(true);
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

    public function testWarmUpCache()
    {
        $this->setCacheExists(false);
        $this->initConfigurationCacheLoader(false);
        $this->configureResourcesAndHash();

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


    public function testWarmUpCacheDevMode()
    {
        $this->setCacheExists(false);
        $this->initConfigurationCacheLoader(true);
        $this->configureResourcesAndHash();

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

    public function testGetConfigurationLocalCache()
    {
        $this->setCacheExists(true);
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

    /**
     * @param bool $debug
     */
    private function initConfigurationCacheLoader($debug)
    {
        $this->configurationCacheLoader = new MappingConfigurationCacheLoader(
            $this->cacheProvider,
            $this->configurationLoader,
            $debug
        );
        $this->configurationCacheLoader->setHashProvider($this->resourceHashProvider);
    }

    /**
     * @param bool $cacheExists
     */
    private function setCacheExists($cacheExists)
    {
        $this->cacheProvider
            ->expects($this->any())
            ->method('contains')
            ->with(MappingConfigurationCacheLoader::CACHE_KEY_HASH)
            ->willReturn($cacheExists);
    }

    private function configureResourcesAndHash()
    {
        $resources = [
            new CumulativeResourceInfo('bundleName', 'name', 'path', ['data' => 'value']),
            new CumulativeResourceInfo('bundleName', 'name', 'path', ['data' => 'value']),
        ];

        $this->configurationLoader
            ->expects($this->once())
            ->method('getResources')
            ->willReturn($resources);

        $this->resourceHashProvider
            ->expects($this->once())
            ->method('getHash')
            ->with($resources)
            ->willReturn(self::GET_HASH_RESULT);
    }
}
