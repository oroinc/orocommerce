<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\AbstractProductImportEventListener;
use Oro\Bundle\CatalogBundle\EventListener\ProductStrategyEventListener;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

class ProductStrategyEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var ProductStrategyEventListener
     */
    private $listener;

    public function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->listener = new ProductStrategyEventListener($this->registry, Category::class);
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
        $organization = new Organization();
        $product->setOrganization($organization);

        $title = 'some title';

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);

        $categoryRepo = $this->createMock(CategoryRepository::class);
        $categoryRepo->expects($this->once())
            ->method('findOneByDefaultTitle')
            ->with($title, $organization)
            ->willReturn(null);
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
        $organization = new Organization();
        $product->setOrganization($organization);

        $title = 'some title';

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);

        $categoryRepo = $this->createMock(CategoryRepository::class);
        $categoryRepo->expects($this->once())
            ->method('findOneByDefaultTitle')
            ->with($title, $organization)
            ->willReturn($category);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($categoryRepo);

        $this->listener->onProcessAfter($event);
        $this->assertSame($category, $product->getCategory());
    }
}
