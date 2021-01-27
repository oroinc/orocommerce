<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
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

    protected function setUp(): void
    {
        $this->registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $this->mockCategoryRepository();
    }

    protected function tearDown(): void
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
                    }

                    return $this->createMock(QueryBuilder::class);
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
}
