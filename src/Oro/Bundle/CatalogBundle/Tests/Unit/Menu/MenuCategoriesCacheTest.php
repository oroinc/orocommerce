<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Menu;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CatalogBundle\Menu\MenuCategoriesCache;
use Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class MenuCategoriesCacheTest extends \PHPUnit\Framework\TestCase
{
    private ArrayAdapter $cache;

    private LocalizedFallbackValueCollectionNormalizer $titlesNormalizer;

    private MenuCategoriesCache $menuCategoriesCache;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->titlesNormalizer = $this->createMock(LocalizedFallbackValueCollectionNormalizer::class);

        $this->menuCategoriesCache = new MenuCategoriesCache($this->cache, $this->titlesNormalizer);
    }

    public function testGetWhenNoCachedData(): void
    {
        $titles = new ArrayCollection([(new LocalizedFallbackValue())->setString('Sample 1')]);
        $categoriesData = [
            [
                'titles' => $titles,
                'sample_key1' => 'sample_value1',
            ],
        ];
        $normalizedTitles = [['normalized_key' => 'normalized_value']];
        $callback = fn () => $categoriesData;

        $this->titlesNormalizer
            ->expects(self::once())
            ->method('normalize')
            ->with($titles)
            ->willReturn($normalizedTitles);

        $this->titlesNormalizer
            ->expects(self::never())
            ->method('denormalize');

        $key = 'sample_key';
        self::assertEquals(
            $categoriesData,
            $this->menuCategoriesCache->get($key, $callback)
        );

        self::assertEquals(
            [['titles' => $normalizedTitles, 'sample_key1' => 'sample_value1',]],
            $this->cache->getItem($key)->get()
        );
    }

    public function testGetWhenHasCachedData(): void
    {
        $titles = new ArrayCollection([(new LocalizedFallbackValue())->setString('Sample 1')]);
        $normalizedTitles = [['normalized_key' => 'normalized_value']];
        $categoriesData = [
            [
                'titles' => $titles,
                'sample_key1' => 'sample_value1',
            ],
        ];
        $key = 'sample_key';
        $cacheItem = $this->cache->getItem($key);
        $cacheItem->set([
            [
                'titles' => $normalizedTitles,
                'sample_key1' => 'sample_value1',
            ],
        ]);
        $this->cache->save($cacheItem);

        $this->titlesNormalizer
            ->expects(self::never())
            ->method('normalize');

        $this->titlesNormalizer
            ->expects(self::once())
            ->method('denormalize')
            ->with($normalizedTitles)
            ->willReturn($titles);

        self::assertEquals(
            $categoriesData,
            $this->menuCategoriesCache->get($key, static fn () => [])
        );

        self::assertEquals(
            [['titles' => $normalizedTitles, 'sample_key1' => 'sample_value1',]],
            $this->cache->getItem($key)->get()
        );
    }

    public function testDelete(): void
    {
        $key = 'sample_key';
        $cacheItem = $this->cache->getItem($key);
        $data = ['sample_key' => 'sample_value'];
        $cacheItem->set($data);
        $this->cache->save($cacheItem);

        self::assertEquals($data, $this->cache->getItem($key)->get());

        $this->menuCategoriesCache->delete($key);

        self::assertNull($this->cache->getItem($key)->get());
    }
}
