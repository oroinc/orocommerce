<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Loader;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;
use Oro\Bundle\WebsiteSearchBundle\Loader\MappingConfigurationCachedLoader;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;

class MappingConfigurationCachedLoaderTest extends \PHPUnit_Framework_TestCase
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
     * @var ResourcesHashProvider
     */
    private $resourceHashProvider;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheProvider;

    /**
     * @var MappingConfigurationCachedLoader
     */
    private $configurationCachedLoader;

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

        $resources = [
            new CumulativeResourceInfo('bundleName', 'name', 'path', ['data' => 'value']),
            new CumulativeResourceInfo('bundleName', 'name', 'path', ['data' => 'value']),
        ];

        $this->configurationLoader
            ->expects($this->any())
            ->method('getResources')
            ->willReturn($resources);

        $this->resourceHashProvider
            ->expects($this->any())
            ->method('getHash')
            ->with($resources)
            ->willReturn(self::GET_HASH_RESULT);
    }

    protected function tearDown()
    {
        unset(
            $this->configurationCachedLoader,
            $this->cacheProvider,
            $this->configurationLoader,
            $this->resourceHashProvider
        );
    }

    public function testGetConfigurationWhenDebugIsOffAndNoCacheExists()
    {
        $this->setCacheExists(false);
        $this->initConfigurationCachedLoader(false);

        $this->assertEquals(self::$configuration, $this->configurationCachedLoader->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOffAndCacheExists()
    {
        $this->setCacheExists(true);
        $this->initConfigurationCachedLoader(false);

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->with(MappingConfigurationCachedLoader::CACHE_KEY_CONFIGURATION)
            ->willReturn(self::$configuration);

        $this->assertEquals(self::$configuration, $this->configurationCachedLoader->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOnAndNoCacheExists()
    {
        $this->setCacheExists(false);
        $this->initConfigurationCachedLoader(true);

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                MappingConfigurationCachedLoader::CACHE_KEY_HASH => self::GET_HASH_RESULT,
                MappingConfigurationCachedLoader::CACHE_KEY_CONFIGURATION => self::$configuration,
            ]);

        $this->assertEquals(self::$configuration, $this->configurationCachedLoader->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOnAndCacheExistsAndHashNotMatchStoredHash()
    {
        $this->setCacheExists(true);
        $this->initConfigurationCachedLoader(true);

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->with(MappingConfigurationCachedLoader::CACHE_KEY_HASH)
            ->willReturn('another_hash');

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                MappingConfigurationCachedLoader::CACHE_KEY_HASH => self::GET_HASH_RESULT,
                MappingConfigurationCachedLoader::CACHE_KEY_CONFIGURATION => self::$configuration,
            ]);

        $this->assertEquals(self::$configuration, $this->configurationCachedLoader->getConfiguration());
    }

    public function testGetConfigurationDataWhenDebugIsOnAndCacheExistsAndHashMatches()
    {
        $this->setCacheExists(true);
        $this->initConfigurationCachedLoader(true);

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [MappingConfigurationCachedLoader::CACHE_KEY_HASH],
                [MappingConfigurationCachedLoader::CACHE_KEY_CONFIGURATION]
            )
            ->willReturnOnConsecutiveCalls(
                self::GET_HASH_RESULT,
                self::$configuration
            );

        $this->assertEquals(self::$configuration, $this->configurationCachedLoader->getConfiguration());
    }

    public function testClearCache()
    {
        $this->initConfigurationCachedLoader(false);

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                [MappingConfigurationCachedLoader::CACHE_KEY_HASH],
                [MappingConfigurationCachedLoader::CACHE_KEY_CONFIGURATION]
            );

        $this->configurationCachedLoader->clearCache();
    }

    public function testWarmUpCache()
    {
        $this->setCacheExists(false);
        $this->initConfigurationCachedLoader(false);

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                [MappingConfigurationCachedLoader::CACHE_KEY_HASH],
                [MappingConfigurationCachedLoader::CACHE_KEY_CONFIGURATION]
            );

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                MappingConfigurationCachedLoader::CACHE_KEY_HASH => self::GET_HASH_RESULT,
                MappingConfigurationCachedLoader::CACHE_KEY_CONFIGURATION => self::$configuration,
            ]);

        $this->configurationCachedLoader->warmUpCache();
    }


    public function testWarmUpCacheDevMode()
    {
        $this->setCacheExists(false);
        $this->initConfigurationCachedLoader(true);

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                [MappingConfigurationCachedLoader::CACHE_KEY_HASH],
                [MappingConfigurationCachedLoader::CACHE_KEY_CONFIGURATION]
            );

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                MappingConfigurationCachedLoader::CACHE_KEY_HASH => self::GET_HASH_RESULT,
                MappingConfigurationCachedLoader::CACHE_KEY_CONFIGURATION => self::$configuration,
            ]);

        $this->configurationCachedLoader->warmUpCache();
    }

    public function testGetConfigurationLocalCache()
    {
        $this->setCacheExists(true);
        $this->initConfigurationCachedLoader(true);

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [MappingConfigurationCachedLoader::CACHE_KEY_HASH],
                [MappingConfigurationCachedLoader::CACHE_KEY_CONFIGURATION]
            )
            ->willReturnOnConsecutiveCalls(
                self::GET_HASH_RESULT,
                []
            );

        $this->configurationCachedLoader->getConfiguration();
        $this->configurationCachedLoader->getConfiguration();
    }

    /**
     * @param bool $debug
     */
    private function initConfigurationCachedLoader($debug)
    {
        $this->configurationCachedLoader = new MappingConfigurationCachedLoader(
            $this->cacheProvider,
            $this->configurationLoader,
            $debug
        );
        $this->configurationCachedLoader->setHashProvider($this->resourceHashProvider);
    }

    /**
     * @param bool $cacheExists
     */
    private function setCacheExists($cacheExists)
    {
        $this->cacheProvider
            ->expects($this->any())
            ->method('contains')
            ->with(MappingConfigurationCachedLoader::CACHE_KEY_HASH)
            ->willReturn($cacheExists);
    }
}
