<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Loader;

use Oro\Bundle\WebsiteSearchBundle\Cache\MappingConfigurationCacheProvider;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;
use Oro\Bundle\WebsiteSearchBundle\Loader\MappingConfigurationLoaderCachingProxy;
use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;
use Oro\Component\Config\CumulativeResourceInfo;

class MappingConfigurationLoaderCachingProxyTest extends \PHPUnit\Framework\TestCase
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
     * @var MappingConfigurationCacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheProvider;

    /**
     * @var MappingConfigurationLoaderCachingProxy
     */
    private $configurationLoaderCachingProxy;

    /**
     * @var ConfigurationLoaderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configurationLoader;

    protected function setUp()
    {
        $this->resourceHashProvider = $this->createMock(ResourcesHashProvider::class);
        $this->cacheProvider = $this->createMock(MappingConfigurationCacheProvider::class);

        $this->configurationLoader = $this->createMock(ConfigurationLoaderInterface::class);
        $this->configurationLoader
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn(self::$configuration);
    }

    protected function tearDown()
    {
        unset(
            $this->configurationLoaderCachingProxy,
            $this->cacheProvider,
            $this->configurationLoader,
            $this->resourceHashProvider
        );
    }

    public function testGetConfigurationWhenDebugIsOffAndNoCacheExists()
    {
        $this->initConfigurationLoaderCachingProxy(false);
        $this->configureResourcesAndHash();

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetchConfiguration')
            ->willReturn([false, []]);

        $this->assertEquals(self::$configuration, $this->configurationLoaderCachingProxy->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOffAndCacheExists()
    {
        $this->initConfigurationLoaderCachingProxy(false);

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetchConfiguration')
            ->willReturn([self::GET_HASH_RESULT, self::$configuration]);

        $this->cacheProvider
            ->expects($this->never())
            ->method('saveConfiguration');

        $this->assertEquals(self::$configuration, $this->configurationLoaderCachingProxy->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOnAndNoCacheExists()
    {
        $this->initConfigurationLoaderCachingProxy(true);
        $this->configureResourcesAndHash();

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetchConfiguration')
            ->willReturn([false, []]);

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveConfiguration')
            ->with(self::GET_HASH_RESULT, self::$configuration);

        $this->assertEquals(self::$configuration, $this->configurationLoaderCachingProxy->getConfiguration());
    }

    public function testGetConfigurationWhenDebugIsOnAndCacheExistsAndHashNotMatchStoredHash()
    {
        $this->initConfigurationLoaderCachingProxy(true);

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetchConfiguration')
            ->willReturn(['another_hash', []]);

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
            ->method('saveConfiguration')
            ->with(self::GET_HASH_RESULT, self::$configuration);

        $this->assertEquals(self::$configuration, $this->configurationLoaderCachingProxy->getConfiguration());
    }

    public function testGetConfigurationDataWhenDebugIsOnAndCacheExistsAndHashMatches()
    {
        $this->initConfigurationLoaderCachingProxy(true);
        $this->configureResourcesAndHash();

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetchConfiguration')
            ->willReturn([self::GET_HASH_RESULT, self::$configuration]);

        $this->assertEquals(self::$configuration, $this->configurationLoaderCachingProxy->getConfiguration());
    }

    public function testGetConfigurationLocalCache()
    {
        $this->initConfigurationLoaderCachingProxy(true);
        $this->configureResourcesAndHash();

        $this->cacheProvider
            ->expects($this->exactly(2))
            ->method('fetchConfiguration')
            ->willReturn([self::GET_HASH_RESULT, []]);

        $this->configurationLoaderCachingProxy->getConfiguration();
        $this->configurationLoaderCachingProxy->getConfiguration();
    }

    public function testGetConfigurationWhenNotCacheProvider()
    {
        $this->configurationLoaderCachingProxy = new MappingConfigurationLoaderCachingProxy(
            $this->configurationLoader,
            $this->resourceHashProvider,
            false
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No mapping cache provider set for');

        $this->configurationLoaderCachingProxy->getConfiguration();
    }

    public function testGetResources()
    {
        $this->initConfigurationLoaderCachingProxy(true);

        $this->configurationLoader
            ->expects($this->once())
            ->method('getResources')
            ->willReturn([]);

        $this->assertEquals([], $this->configurationLoaderCachingProxy->getResources());
        $this->assertEquals([], $this->configurationLoaderCachingProxy->getResources());
    }

    public function testIsCachingProxyFullyConfigured()
    {
        $this->initConfigurationLoaderCachingProxy(false);

        self::assertTrue($this->configurationLoaderCachingProxy->isCachingProxyFullyConfigured());

        $this->configurationLoaderCachingProxy = new MappingConfigurationLoaderCachingProxy(
            $this->configurationLoader,
            $this->resourceHashProvider,
            false
        );

        self::assertFalse($this->configurationLoaderCachingProxy->isCachingProxyFullyConfigured());
    }

    /**
     * @param bool $debug
     */
    private function initConfigurationLoaderCachingProxy($debug)
    {
        $this->configurationLoaderCachingProxy = new MappingConfigurationLoaderCachingProxy(
            $this->configurationLoader,
            $this->resourceHashProvider,
            $debug
        );

        $this->configurationLoaderCachingProxy->setMappingConfigurationCacheProvider($this->cacheProvider);
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
