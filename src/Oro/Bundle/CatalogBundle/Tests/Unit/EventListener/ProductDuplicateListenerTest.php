<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\ProductDuplicateListener;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;

class ProductDuplicateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private $categoryClass = 'stdClass';

    /** @var Category */
    private $category;

    /** @var ProductDuplicateListener */
    private $listener;

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var ObjectManager */
    private $objectManager;

    /** @var Product */
    private $product;

    /** @var Product */
    private $sourceProduct;

    protected function setUp(): void
    {
        $this->product = new Product();
        $this->sourceProduct = new Product();
        $this->category = new Category();

        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->objectManager = $this->createMock(EntityManager::class);

        $this->categoryRepository->expects($this->once())
            ->method('findOneByProduct')
            ->willReturnCallback(function (Product $product) {
                return $this->category->getProducts()->contains($product)
                    ? $this->category
                    : null;
            });

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->categoryClass)
            ->willReturn($this->categoryRepository);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($this->categoryClass)
            ->willReturn($this->objectManager);

        $this->listener = new ProductDuplicateListener();
        $this->listener->setCategoryClass($this->categoryClass);
        $this->listener->setDoctrineHelper($doctrineHelper);
    }

    public function testOnDuplicateAfterSourceProductLinkedWithCategory()
    {
        $this->category->getProducts()->clear();
        $this->category->addProduct($this->sourceProduct);

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->listener->onDuplicateAfter($event);

        $this->assertCount(2, $this->category->getProducts());
        $this->assertTrue($this->category->getProducts()->contains($this->product));
    }

    public function testOnDuplicateAfterSourceProductNotLinkedWithCategory()
    {
        $this->category->getProducts()->clear();

        $event = new ProductDuplicateAfterEvent($this->product, $this->sourceProduct);

        $this->listener->onDuplicateAfter($event);

        $this->assertCount(0, $this->category->getProducts());
    }
}
