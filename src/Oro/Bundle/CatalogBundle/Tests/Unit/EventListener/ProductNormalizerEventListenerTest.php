<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\ProductNormalizerEventListener;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category as CategoryStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\ReflectionUtil;

class ProductNormalizerEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryRepository;

    /** @var ProductNormalizerEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->categoryRepository = $this->createMock(CategoryRepository::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $this->listener = new ProductNormalizerEventListener(
            $registry,
            $this->aclHelper,
            Category::class
        );
    }

    private function getProduct(): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $product->setSku(uniqid('', true));

        return $product;
    }

    private function getCategory(Product $product): Category
    {
        $category = new CategoryStub();
        $category->addTitle(new CategoryTitle());
        $category->addProduct($product);

        return $category;
    }

    public function testOnNormalize()
    {
        $product = $this->getProduct();
        $category = $this->getCategory($product);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->categoryRepository->expects($this->once())
            ->method('findOneByProductSkuQueryBuilder')
            ->with($product->getSku(), $this->isTrue())
            ->willReturn($queryBuilder);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($category);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($this->identicalTo($queryBuilder))
            ->willReturn($query);

        $event = new ProductNormalizerEvent($product, []);
        $this->listener->onNormalize($event);
        $this->assertEquals($product, $event->getProduct());

        $plainData = $event->getPlainData();
        $this->assertArrayHasKey(ProductNormalizerEventListener::CATEGORY_KEY, $plainData);
        $this->assertEquals(
            $category->getDefaultTitle(),
            $plainData[ProductNormalizerEventListener::CATEGORY_KEY]
        );

        // test that a cache is used
        $this->listener->onNormalize($event);
    }

    public function testOnClear()
    {
        $product = $this->getProduct();
        $category = $this->getCategory($product);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->categoryRepository->expects($this->exactly(2))
            ->method('findOneByProductSkuQueryBuilder')
            ->with($product->getSku(), $this->isTrue())
            ->willReturn($queryBuilder);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->exactly(2))
            ->method('getOneOrNullResult')
            ->willReturn($category);
        $this->aclHelper->expects($this->exactly(2))
            ->method('apply')
            ->with($this->identicalTo($queryBuilder))
            ->willReturn($query);

        $event = new ProductNormalizerEvent($product, []);
        $this->listener->onNormalize($event);
        $this->listener->onClear();
        $this->listener->onNormalize($event);
    }

    public function testOnNormalizeWithoutCategory()
    {
        $product = $this->getProduct();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->categoryRepository->expects($this->once())
            ->method('findOneByProductSkuQueryBuilder')
            ->with($product->getSku(), $this->isTrue())
            ->willReturn($queryBuilder);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($this->identicalTo($queryBuilder))
            ->willReturn($query);

        $event = new ProductNormalizerEvent($product, []);
        $this->listener->onNormalize($event);
        $this->assertArrayNotHasKey(ProductNormalizerEventListener::CATEGORY_KEY, $event->getPlainData());
    }
}
