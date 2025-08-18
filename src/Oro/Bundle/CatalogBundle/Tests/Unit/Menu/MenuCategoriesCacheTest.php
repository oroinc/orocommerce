<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Menu;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CatalogBundle\Menu\MenuCategoriesCache;
use Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class MenuCategoriesCacheTest extends \PHPUnit\Framework\TestCase
{
    private ArrayAdapter $cache;
    private MenuCategoriesCache $menuCategoriesCache;

    #[\Override]
    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();

        $titlesNormalizer = $this->createMock(LocalizedFallbackValueCollectionNormalizer::class);
        $titlesNormalizer->expects(self::any())
            ->method('normalize')
            ->willReturnCallback(function (iterable $localizedFallbackValues) {
                $normalizedData = [];
                /** @var AbstractLocalizedFallbackValue $val */
                foreach ($localizedFallbackValues as $val) {
                    $normalizedData[] = ['s' => $val->getString()];
                }

                return $normalizedData;
            });
        $titlesNormalizer->expects(self::any())
            ->method('denormalize')
            ->willReturnCallback(function (array $normalizedData, string $entityClass) {
                self::assertEquals(LocalizedFallbackValue::class, $entityClass);
                $collection = [];
                foreach ($normalizedData as $item) {
                    $collection[] = (new LocalizedFallbackValue())->setString($item['s']);
                }

                return new ArrayCollection($collection);
            });

        $this->menuCategoriesCache = new MenuCategoriesCache($this->cache, $titlesNormalizer);
    }

    public function testGetWhenNoCachedData(): void
    {
        $titles = new ArrayCollection([(new LocalizedFallbackValue())->setString('Sample 1')]);
        $categoriesData = [['titles' => $titles, 'sample_key1' => 'sample_value1']];

        $key = 'sample_key';
        self::assertEquals(
            $categoriesData,
            $this->menuCategoriesCache->get($key, function () use ($categoriesData) {
                return $categoriesData;
            })
        );

        self::assertEquals(
            [['titles' => [['s' => 'Sample 1']], 'sample_key1' => 'sample_value1']],
            $this->cache->getItem($key)->get()
        );
    }

    public function testGetWhenHasCachedData(): void
    {
        $titles = new ArrayCollection([(new LocalizedFallbackValue())->setString('Sample 1')]);
        $normalizedTitles = [['s' => 'Sample 1']];
        $categoriesData = [['titles' => $titles, 'sample_key1' => 'sample_value1']];
        $key = 'sample_key';
        $cacheItem = $this->cache->getItem($key);
        $cacheItem->set([['titles' => $normalizedTitles, 'sample_key1' => 'sample_value1']]);
        $this->cache->save($cacheItem);

        self::assertEquals(
            $categoriesData,
            $this->menuCategoriesCache->get($key, function () {
                return [];
            })
        );

        self::assertEquals(
            [['titles' => $normalizedTitles, 'sample_key1' => 'sample_value1']],
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
