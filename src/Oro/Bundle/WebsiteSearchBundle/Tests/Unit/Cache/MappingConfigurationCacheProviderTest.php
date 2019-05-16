<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\WebsiteSearchBundle\Cache\MappingConfigurationCacheProvider;

class MappingConfigurationCacheProviderTest extends \PHPUnit\Framework\TestCase
{
    private const CACHE_KEY_HASH = 'cached_hash';
    private const CACHE_KEY_CONFIGURATION = ['cached' => 'config'];

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheProvider;

    /**
     * @var MappingConfigurationCacheProvider
     */
    private $mappingConfigurationCacheProvider;

    protected function setUp()
    {
        $this->cacheProvider = $this->createMock(CacheProvider::class);

        $this->mappingConfigurationCacheProvider = new MappingConfigurationCacheProvider($this->cacheProvider);
    }

    /**
     * @dataProvider fetchDataProvider
     * @param array $cachedValues
     * @param array $expectedValues
     */
    public function testFetchConfiguration(array $cachedValues, array $expectedValues): void
    {
        $this->cacheProvider
            ->expects(self::once())
            ->method('fetchMultiple')
            ->with(['cache_key_hash', 'cache_key_configuration'])
            ->willReturn($cachedValues);

        self::assertEquals($expectedValues, $this->mappingConfigurationCacheProvider->fetchConfiguration());
    }

    /**
     * @return array
     */
    public function fetchDataProvider(): array
    {
        return [
            'hash and configuration successfully fetched' => [
                'cachedValues' => [
                    'cache_key_hash' => 'cached_hash',
                    'cache_key_configuration' => ['cached' => 'config']
                ],
                'expectedValues' => [
                    'cached_hash',
                    ['cached' => 'config']
                ]
            ],
            'configuration successfully fetched without hash' => [
                'cachedValues' => [
                    'cache_key_configuration' => ['cached' => 'config']
                ],
                'expectedValues' => [
                    false,
                    []
                ]
            ],
            'hash successfully fetched without configuration' => [
                'cachedValues' => [
                    'cache_key_hash' => 'cached_hash',
                ],
                'expectedValues' => [
                    false,
                    []
                ]
            ],
        ];
    }

    public function testSaveConfiguration(): void
    {
        $hash = 'some_hash';
        $configuration = ['config' => 'value'];

        $this->cacheProvider
            ->expects(self::once())
            ->method('saveMultiple')
            ->with([
                'cache_key_hash' => $hash,
                'cache_key_configuration' => $configuration
            ]);

        $this->mappingConfigurationCacheProvider->saveConfiguration($hash, $configuration);
    }

    public function testDeleteConfiguration(): void
    {
        $this->cacheProvider
            ->expects(self::once())
            ->method('deleteMultiple')
            ->with(['cache_key_hash', 'cache_key_configuration']);

        $this->mappingConfigurationCacheProvider->deleteConfiguration();
    }

    public function testFetchConfigurationCache(): void
    {
        $this->cacheProvider
            ->expects(self::once())
            ->method('fetchMultiple')
            ->with(['cache_key_hash', 'cache_key_configuration'])
            ->willReturn([
                'cache_key_hash' => self::CACHE_KEY_HASH,
                'cache_key_configuration' => self::CACHE_KEY_CONFIGURATION
            ]);

        $this->mappingConfigurationCacheProvider->fetchConfiguration();

        // Additional call does not trigger cache provider
        $this->mappingConfigurationCacheProvider->fetchConfiguration();
    }

    public function testFetchConfigurationCacheAfterDelete(): void
    {
        $this->cacheProvider
            ->expects(self::exactly(2))
            ->method('fetchMultiple')
            ->with(['cache_key_hash', 'cache_key_configuration'])
            ->willReturn([
                'cache_key_hash' => self::CACHE_KEY_HASH,
                'cache_key_configuration' => self::CACHE_KEY_CONFIGURATION
            ]);

        $this->cacheProvider
            ->expects(self::once())
            ->method('deleteMultiple')
            ->with(['cache_key_hash', 'cache_key_configuration']);

        $this->mappingConfigurationCacheProvider->fetchConfiguration();

        $this->mappingConfigurationCacheProvider->deleteConfiguration();

        // Additional call does trigger cache provider after configuration deletion
        $this->mappingConfigurationCacheProvider->fetchConfiguration();
    }

    public function testFetchConfigurationCacheAfterSave(): void
    {
        $this->cacheProvider
            ->expects(self::exactly(2))
            ->method('fetchMultiple')
            ->with(['cache_key_hash', 'cache_key_configuration'])
            ->willReturn([
                'cache_key_hash' => self::CACHE_KEY_HASH,
                'cache_key_configuration' => self::CACHE_KEY_CONFIGURATION
            ]);

        $newHash = 'new_hash';
        $newConfig = ['new' => 'config'];
        $this->cacheProvider
            ->expects(self::once())
            ->method('saveMultiple')
            ->with([
                'cache_key_hash' => $newHash,
                'cache_key_configuration' => $newConfig
            ]);

        $this->mappingConfigurationCacheProvider->fetchConfiguration();

        $this->mappingConfigurationCacheProvider->saveConfiguration($newHash, $newConfig);

        // Additional call does trigger cache provider after configuration save
        $this->mappingConfigurationCacheProvider->fetchConfiguration();
    }
}
