<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\AbstractProductImportEventListener;
use Oro\Bundle\CatalogBundle\EventListener\ProductStrategyEventListener;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ProductStrategyEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /**
     * @var ProductStrategyEventListener
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->listener = new ProductStrategyEventListener($this->registry, $this->aclHelper, Category::class);
    }

    public function testOnProcessAfterWithoutCategoryKey()
    {
        $product = new Product();
        $event = new ProductStrategyEvent($product, []);
        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->listener->onProcessAfter($event);
    }

    public function testOnProcessAfterWithoutCategory()
    {
        $product = new Product();
        $title = 'some title';

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->willReturn($query);
        $categoryRepo = $this->createMock(CategoryRepository::class);
        $categoryRepo->expects($this->once())
            ->method('findOneByDefaultTitleQueryBuilder')
            ->with($title)
            ->willReturn($queryBuilder);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($categoryRepo);

        $this->listener->onProcessAfter($event);
        $this->assertEmpty($product->getCategory());
    }

    public function testOnProcessAfter()
    {
        $product = new Product();
        $category = new Category();
        $title = 'some title';

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($category);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->willReturn($query);

        $categoryRepo = $this->createMock(CategoryRepository::class);
        $categoryRepo->expects($this->once())
            ->method('findOneByDefaultTitleQueryBuilder')
            ->with($title)
            ->willReturn($queryBuilder);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($categoryRepo);

        $this->listener->onProcessAfter($event);
        $this->assertSame($category, $product->getCategory());
    }
}
