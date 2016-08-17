<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Bundle\WebsiteSearchBundle\Cache\ResourcesDependentCachingConfigurationProvider;
use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesBasedConfigurationProviderInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ResourcesDependentCachingConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    use ConfigResourcePathTrait;

    /**
     * @var array
     */
    private static $configuration = [
        'OroB2B\Bundle\TestPageBundle\Entity\Page' => [
            'alias' => 'page_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'title_LOCALIZATION_ID',
                    'type' => 'text'
                ],
            ]
        ]
    ];

    /**
     * @var ResourcesDependentCachingConfigurationProvider
     */
    protected $provider;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheProvider;

    /**
     * @var ResourcesBasedConfigurationProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationProvider;

    protected function setUp()
    {
        $this->cacheProvider = $this
            ->getMockBuilder(CacheProvider::class)
            ->getMock();

        $this->configurationProvider = $this
            ->getMockBuilder(ResourcesBasedConfigurationProviderInterface::class)
            ->getMock();
    }

    /**
     * @param bool $debug
     */
    protected function initProvider($debug)
    {
        $this->provider = new ResourcesDependentCachingConfigurationProvider(
            $this->cacheProvider,
            $debug,
            $this->configurationProvider
        );
    }

    /**
     * @param CumulativeResourceInfo[] $resources
     */
    protected function setConfigurationProviderResources($resources = [])
    {
        $this->configurationProvider
            ->expects($this->once())
            ->method('getResources')
            ->willReturn($resources);
    }

    /**
     * @param array $resourcesPaths
     * @return CumulativeResourceInfo[]
     */
    protected function generateResourcesByPaths(array $resourcesPaths)
    {
        $resources = [];
        foreach ($resourcesPaths as $resourcePath) {
            $resource = new CumulativeResourceInfo('', '', $resourcePath);

            $resources[] = $resource;
        }

        return $resources;
    }

    protected function setConfigurationProviderConfiguration()
    {
        $this->configurationProvider
            ->expects($this->once())
            ->method('getConfiguration')
            ->willReturn(self::$configuration);
    }

    /**
     * @param bool $cacheExists
     */
    protected function setCacheExists($cacheExists)
    {
        $this->cacheProvider
            ->expects($this->once())
            ->method('contains')
            ->with('cache_key_resources')
            ->willReturn($cacheExists);
    }

    public function testGetConfigurationDataWhenDebugIsOffAndNoCacheExists()
    {
        $this->setCacheExists(false);

        $this->setConfigurationProviderResources();
        $this->setConfigurationProviderConfiguration();

        $this->initProvider(false);

        $this->assertEquals(self::$configuration, $this->provider->getConfigurationData());
    }

    public function testGetConfigurationDataWhenDebugIsOffAndCacheExists()
    {
        $this->setCacheExists(true);

        $this->initProvider(false);

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->with('cache_key_configuration')
            ->willReturn(serialize(self::$configuration));

        $this->assertEquals(self::$configuration, $this->provider->getConfigurationData());
    }

    public function testGetConfigurationDataWhenDebugIsOnAndNoCacheExists()
    {
        $this->setCacheExists(false);

        $this->setConfigurationProviderResources();
        $this->setConfigurationProviderConfiguration();

        $this->initProvider(true);

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                'cache_key_resources' => serialize([]),
                'cache_key_last_modification_time' => 1,
                'cache_key_configuration' => serialize(self::$configuration)
            ]);

        $this->assertEquals(self::$configuration, $this->provider->getConfigurationData());
    }

    public function testGetConfigurationDataWhenDebugIsOnAndCacheExistsAndResourcesNotMatchStoredResources()
    {
        $this->setCacheExists(true);

        $this->setConfigurationProviderResources();
        $this->setConfigurationProviderConfiguration();

        $this->initProvider(true);

        $storedResources = $this->generateResourcesByPaths([
            $this->getBundleConfigResourcePath('TestPageBundle', 'website_search.yml')
        ]);

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->withConsecutive(['cache_key_resources'], ['cache_key_configuration'])
            ->will($this->onConsecutiveCalls(serialize($storedResources), serialize([])));

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                'cache_key_resources' => serialize([]),
                'cache_key_last_modification_time' => 1,
                'cache_key_configuration' => serialize(self::$configuration)
            ]);

        $this->assertEquals(self::$configuration, $this->provider->getConfigurationData());
    }

    public function testGetConfigurationDataWhenDebugIsOnAndCacheExistsAndResourcesAreModified()
    {
        $resourcePath = $this->getBundleConfigResourcePath('TestPageBundle', 'website_search.yml');
        $storedResources = $this->generateResourcesByPaths([$resourcePath]);

        $this->setCacheExists(true);

        $this->setConfigurationProviderResources($storedResources);
        $this->setConfigurationProviderConfiguration();

        $this->initProvider(true);

        $lastModificationTime = 0;

        $this->cacheProvider
            ->method('fetch')
            ->withConsecutive(
                ['cache_key_resources'],
                ['cache_key_last_modification_time']
            )
            ->will($this->onConsecutiveCalls(
                serialize($storedResources),
                $lastModificationTime
            ));

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                'cache_key_resources' => serialize($storedResources),
                'cache_key_last_modification_time' => filemtime($resourcePath) + 1,
                'cache_key_configuration' => serialize(self::$configuration)
            ]);

        $this->assertEquals(self::$configuration, $this->provider->getConfigurationData());
    }

    public function testGetConfigurationDataWhenDebugIsOnAndCacheExistsAndResourcesAreActual()
    {
        $resourcePath = $this->getBundleConfigResourcePath('TestPageBundle', 'website_search.yml');
        $storedResources = $this->generateResourcesByPaths([$resourcePath]);

        $this->setCacheExists(true);

        $this->setConfigurationProviderResources($storedResources);

        $this->initProvider(true);

        $lastModificationTime = filemtime($resourcePath);

        $this->cacheProvider
            ->method('fetch')
            ->withConsecutive(
                ['cache_key_resources'],
                ['cache_key_last_modification_time'],
                ['cache_key_configuration']
            )
            ->will($this->onConsecutiveCalls(
                serialize($storedResources),
                $lastModificationTime,
                serialize(self::$configuration)
            ));

        $this->assertEquals(self::$configuration, $this->provider->getConfigurationData());
    }
}
