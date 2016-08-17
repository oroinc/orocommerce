<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Bundle\WebsiteSearchBundle\Provider\ConfigurationCacheProvider;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\ConfigResourcePathTrait;

class ConfigurationCacheProviderTest extends \PHPUnit_Framework_TestCase
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
     * @var ConfigurationCacheProvider
     */
    private $provider;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheProvider;

    /**
     * @var ConfigurationLoaderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configurationProvider;

    /**
     * @var ResourcesHashProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $hashProvider;

    protected function setUp()
    {
        $this->cacheProvider = $this->getMockBuilder(CacheProvider::class)
            ->getMock();

        $this->configurationProvider = $this->getMockBuilder(ConfigurationLoaderInterface::class)
            ->getMock();

        $this->hashProvider = $this->getMockBuilder(ResourcesHashProvider::class)
            ->getMock();
    }

    /**
     * @param bool $debug
     */
    private function initProvider($debug)
    {
        $this->provider = new ConfigurationCacheProvider(
            $this->cacheProvider, $this->configurationProvider, $this->hashProvider, $debug
        );
    }

    /**
     * @param CumulativeResourceInfo[] $resources
     */
    private function setConfigurationProviderResources($resources)
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
    private function generateResourcesByPaths(array $resourcesPaths)
    {
        $resources = [];
        foreach ($resourcesPaths as $resourcePath) {
            $resource = new CumulativeResourceInfo('', '', $resourcePath);

            $resources[] = $resource;
        }

        return $resources;
    }

    private function setConfigurationProviderConfiguration()
    {
        $this->configurationProvider
            ->expects($this->once())
            ->method('getConfiguration')
            ->willReturn(self::$configuration);
    }

    /**
     * @param bool $cacheExists
     */
    private function setCacheExists($cacheExists)
    {
        $this->cacheProvider
            ->expects($this->once())
            ->method('contains')
            ->with('cache_key_hash')
            ->willReturn($cacheExists);
    }

    /**
     * @return CumulativeResourceInfo[]
     */
    private function getBundlesResources(array $bundles, $resourceFile)
    {
        $resourcesPaths = [];
        foreach ($bundles as $bundle) {
            $resourcesPaths[] = $this->getBundleConfigResourcePath($bundle, $resourceFile);
        }

        return $this->generateResourcesByPaths($resourcesPaths);
    }

    public function testGetConfigurationDataWhenDebugIsOffAndNoCacheExists()
    {
        $this->setCacheExists(false);

        $this->setConfigurationProviderResources(
            $this->getBundlesResources(['TestPageBundle',], 'website_search.yml')
        );

        $this->setConfigurationProviderConfiguration();

        $this->initProvider(false);

        $this->assertEquals(self::$configuration, $this->provider->getConfiguration());
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

        $this->assertEquals(self::$configuration, $this->provider->getConfiguration());
    }

    public function testGetConfigurationDataWhenDebugIsOnAndNoCacheExists()
    {
        $this->setCacheExists(false);

        $resources = $this->getBundlesResources(['TestPageBundle',], 'website_search.yml');
        $this->setConfigurationProviderResources($resources);

        $this->setConfigurationProviderConfiguration();

        $this->initProvider(true);

        $someHashString = 'some_hash_string';

        $this->hashProvider
            ->expects($this->once())
            ->method('getHash')
            ->with($resources)
            ->willReturn($someHashString);

        $this->cacheProvider
            ->expects($this->once())
            ->method('saveMultiple')
            ->with([
                'cache_key_hash' => $someHashString,
                'cache_key_configuration' => serialize(self::$configuration)
            ]);

        $this->assertEquals(self::$configuration, $this->provider->getConfiguration());
    }

    public function testGetConfigurationDataWhenDebugIsOnAndCacheExistsAndHashNotMatchStoredHash()
    {
        $this->setCacheExists(true);

        $resources = $this->getBundlesResources(['TestPageBundle',], 'website_search.yml');
        $this->setConfigurationProviderResources($resources);
        $this->setConfigurationProviderConfiguration();

        $this->initProvider(true);

        $this->hashProvider
            ->expects($this->exactly(2))
            ->method('getHash')
            ->with($resources)
            ->willReturn('calculated_hash');

        $this->cacheProvider
            ->expects($this->once())
            ->method('fetch')
            ->withConsecutive(['cache_key_hash'], ['cache_key_configuration'])
            ->will($this->onConsecutiveCalls('stored_hash', serialize([])));

        $this->assertEquals(self::$configuration, $this->provider->getConfiguration());
    }

    public function testGetConfigurationDataWhenDebugIsOnAndCacheExistsAndHashMatches()
    {
        $storedResources = $this->getBundlesResources(['TestPageBundle',], 'website_search.yml');

        $this->setCacheExists(true);

        $this->setConfigurationProviderResources($storedResources);

        $this->initProvider(true);

        $someHash = 'some_hash';
        $this->hashProvider
            ->expects($this->once())
            ->method('getHash')
            ->with($storedResources)
            ->willReturn($someHash);

        $this->cacheProvider
            ->method('fetch')
            ->withConsecutive(
                ['cache_key_hash'],
                ['cache_key_configuration']
            )
            ->will($this->onConsecutiveCalls(
                $someHash,
                serialize(self::$configuration)
            ));

        $this->assertEquals(self::$configuration, $this->provider->getConfiguration());
    }
}
