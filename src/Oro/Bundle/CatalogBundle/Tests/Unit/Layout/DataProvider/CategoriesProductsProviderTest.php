<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoriesProductsProvider;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;

class CategoriesProductsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryRepository;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $searchRepository;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var CategoriesProductsProvider */
    private $categoriesProductsProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->searchRepository = $this->createMock(ProductRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->categoriesProductsProvider = new CategoriesProductsProvider(
            $registry,
            $this->searchRepository
        );

        $this->cache = $this->createMock(CacheProvider::class);
        $this->categoriesProductsProvider->setCache($this->cache);
    }

    public function testGetCountByCategories()
    {
        $this->categoryRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1, 2, 3]])
            ->willReturn([]);

        $this->searchRepository
            ->expects($this->once())
            ->method('getCategoriesCounts')
            ->with([])
            ->willReturn(35);

        $this->cache->expects($this->exactly(2))
            ->method('save');

        $actual = $this->categoriesProductsProvider->getCountByCategories([1, 2, 3]);
        $this->assertEquals(35, $actual);
    }

    public function testGetCountByCategoriesCached()
    {
        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with('cacheVal_categories_products_1_2_3')
            ->willReturn(35);

        $this->categoryRepository
            ->expects($this->never())
            ->method('findBy');

        $this->searchRepository
            ->expects($this->never())
            ->method('getCategoriesCounts');

        $this->cache
            ->expects($this->never())
            ->method('save');

        $actual = $this->categoriesProductsProvider->getCountByCategories([1, 2, 3]);
        $this->assertEquals(35, $actual);
    }
}
