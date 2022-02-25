<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoriesProductsProvider;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class CategoriesProductsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryRepository;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $searchRepository;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var CategoriesProductsProvider */
    private $categoriesProductsProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->searchRepository = $this->createMock(ProductRepository::class);
        $this->cache = $this->createMock(AbstractAdapter::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->categoriesProductsProvider = new CategoriesProductsProvider(
            $doctrine,
            $this->searchRepository
        );
        $this->categoriesProductsProvider->setCache($this->cache);
    }

    public function testGetCountByCategoriesCached()
    {
        $categoriesIds = [1, 2, 3];
        $result = [1 => 35];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('categories_products_1_2_3')
            ->willReturn($result);

        $this->categoryRepository
            ->expects($this->never())
            ->method('findBy');

        $this->searchRepository
            ->expects($this->never())
            ->method('getCategoriesCounts');

        $actual = $this->categoriesProductsProvider->getCountByCategories($categoriesIds);
        $this->assertEquals($result, $actual);
    }
}
