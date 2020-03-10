<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractProductImportEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const CATEGORY_CLASS = 'Oro\Bundle\CatalogBundle\Entity\Category';

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $categoryRepository;

    /**
     * @var Category[]
     */
    protected $categoriesByProduct = [];

    /**
     * Count of repository `findOneByProductSku` calls by SKU
     *
     * @var int[]
     */
    protected $findByProductSkuCalls = [];

    /**
     * @var Category[]
     */
    protected $categoriesByTitle = [];

    /**
     * Count of repository `findOneByDefaultTitle` calls by title
     *
     * @var int[]
     */
    protected $findByDefaultTitleCalls = [];

    public function setUp()
    {
        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->mockCategoryRepository();
    }

    public function tearDown()
    {
        unset($this->registry, $this->categoryRepository);
    }

    private function mockCategoryRepository()
    {
        $this->categoryRepository = $this
            ->getMockBuilder('Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryRepository
            ->expects($this->any())
            ->method('findOneByProductSkuQueryBuilder')
            ->willReturnCallback(
                function ($sku) {
                    if (!isset($this->categoriesByProduct[$sku])) {
                        $result = null;
                    } else {
                        $this->findByProductSkuCalls[$sku]++;
                        $result = $this->categoriesByProduct[$sku];
                    }

                    $query = $this->createMock(AbstractQuery::class);
                    $query->expects($this->once())
                        ->method('getOneOrNullResult')
                        ->willReturn($result);
                    $queryBuilder = $this->createMock(QueryBuilder::class);
                    $queryBuilder->expects($this->once())
                        ->method('andWhere')
                        ->with('category.organization = :organization')
                        ->willReturnSelf();
                    $queryBuilder->expects($this->once())
                        ->method('setParameter')
                        ->willReturnSelf();
                    $queryBuilder->expects($this->once())
                        ->method('getQuery')
                        ->willReturn($query);

                    return $queryBuilder;
                }
            );

        $this->categoryRepository
            ->expects($this->any())
            ->method('findOneByDefaultTitle')
            ->willReturnCallback(
                function ($title) {
                    if (!isset($this->categoriesByTitle[$title])) {
                        return null;
                    }

                    $this->findByDefaultTitleCalls[$title]++;

                    return $this->categoriesByTitle[$title];
                }
            );

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(self::CATEGORY_CLASS)
            ->willReturn($this->categoryRepository);
    }

    /**
     * @return Product
     */
    protected function getPreparedProduct()
    {
        $sku = uniqid('', true);

        /** @var Product $product */
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 1, 'sku' => $sku]);

        $category = new Category();
        $title = new CategoryTitle();
        $category->addTitle($title);
        $category->addProduct($product);

        $this->findByProductSkuCalls[$sku] = 0;
        $this->categoriesByProduct[$sku] = $category;

        return $product;
    }

    /**
     * @param string $title
     * @return string
     */
    protected function prepareTitle($title)
    {
        $this->findByDefaultTitleCalls[$title] = 0;

        $category = new Category();
        $fallbackValue = new LocalizedFallbackValue();
        $category->addTitle($fallbackValue);

        $this->categoriesByTitle[$title] = $category;

        return $title;
    }
}
