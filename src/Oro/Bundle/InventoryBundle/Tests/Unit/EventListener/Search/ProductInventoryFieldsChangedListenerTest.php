<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Search;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValueRepository;
use Oro\Bundle\InventoryBundle\EventListener\Search\ProductInventoryFieldsChangedListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\SearchBundle\Utils\IndexationEntitiesContainer;
use Oro\Component\Testing\ReflectionUtil;

class ProductInventoryFieldsChangedListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var IndexationEntitiesContainer|\PHPUnit\Framework\MockObject\MockObject */
    private $changedEntities;

    /** @var ProductReindexManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productReindexManager;

    /** @var ProductInventoryFieldsChangedListener */
    private $listener;

    protected function setUp(): void
    {
        $this->changedEntities = $this->createMock(IndexationEntitiesContainer::class);
        $this->productReindexManager = $this->createMock(ProductReindexManager::class);

        $this->listener = new ProductInventoryFieldsChangedListener(
            $this->changedEntities,
            $this->productReindexManager
        );
    }

    private function getEntityFieldFallbackValue(int $id): EntityFieldFallbackValue
    {
        $value = new EntityFieldFallbackValue();
        ReflectionUtil::setId($value, $id);

        return $value;
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getCategory(int $id): Category
    {
        $category = new Category();
        ReflectionUtil::setId($category, $id);

        return $category;
    }

    public function testPostUpdateWhenValueIsNotApplicable(): void
    {
        $value = $this->getEntityFieldFallbackValue(123);
        $em = $this->createMock(EntityManagerInterface::class);
        $entityFieldFallbackValueRepo = $this->createMock(EntityFieldFallbackValueRepository::class);
        $categoryRepo = $this->createMock(CategoryRepository::class);

        $em->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [EntityFieldFallbackValue::class, $entityFieldFallbackValueRepo],
                [Category::class, $categoryRepo]
            ]);

        $entityFieldFallbackValueRepo->expects(self::exactly(6))
            ->method('findEntityId')
            ->withConsecutive(
                [Product::class, 'lowInventoryThreshold', $value->getId()],
                [Product::class, 'highlightLowInventory', $value->getId()],
                [Product::class, 'isUpcoming', $value->getId()],
                [Category::class, 'lowInventoryThreshold', $value->getId()],
                [Category::class, 'highlightLowInventory', $value->getId()],
                [Category::class, 'isUpcoming', $value->getId()]
            )
            ->willReturn(null);

        $categoryRepo->expects(self::never())
            ->method('getProductIdsByCategories');

        $this->changedEntities->expects(self::never())
            ->method('addEntity');

        $this->listener->postUpdate($value, new LifecycleEventArgs($value, $em));
    }

    public function testPostUpdateWhenValueIsApplicableForProduct(): void
    {
        $value = $this->getEntityFieldFallbackValue(123);
        $em = $this->createMock(EntityManagerInterface::class);
        $entityFieldFallbackValueRepo = $this->createMock(EntityFieldFallbackValueRepository::class);
        $categoryRepo = $this->createMock(CategoryRepository::class);
        $productId = 100;
        $product = $this->getProduct($productId);

        $em->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [EntityFieldFallbackValue::class, $entityFieldFallbackValueRepo],
                [Category::class, $categoryRepo]
            ]);
        $em->expects(self::once())
            ->method('getReference')
            ->with(Product::class, $productId)
            ->willReturn($product);

        $entityFieldFallbackValueRepo->expects(self::exactly(2))
            ->method('findEntityId')
            ->withConsecutive(
                [Product::class, 'lowInventoryThreshold', $value->getId()],
                [Product::class, 'highlightLowInventory', $value->getId()]
            )
            ->willReturnOnConsecutiveCalls(null, $productId);

        $categoryRepo->expects(self::never())
            ->method('getProductIdsByCategories');

        $this->changedEntities->expects(self::once())
            ->method('addEntity')
            ->with($product);

        $this->listener->postUpdate($value, new LifecycleEventArgs($value, $em));
    }

    public function testPostUpdateWhenValueIsApplicableForCategory(): void
    {
        $value = $this->getEntityFieldFallbackValue(123);
        $em = $this->createMock(EntityManagerInterface::class);
        $entityFieldFallbackValueRepo = $this->createMock(EntityFieldFallbackValueRepository::class);
        $categoryRepo = $this->createMock(CategoryRepository::class);
        $categoryId = 10;
        $productId = 100;
        $category = $this->getCategory($categoryId);
        $product = $this->getProduct($productId);

        $em->expects(self::any())
            ->method('getRepository')
            ->willReturnMap([
                [EntityFieldFallbackValue::class, $entityFieldFallbackValueRepo],
                [Category::class, $categoryRepo]
            ]);
        $em->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnMap([
                [Category::class, $categoryId, $category],
                [Product::class, $productId, $product]
            ]);

        $entityFieldFallbackValueRepo->expects(self::exactly(5))
            ->method('findEntityId')
            ->withConsecutive(
                [Product::class, 'lowInventoryThreshold', $value->getId()],
                [Product::class, 'highlightLowInventory', $value->getId()],
                [Product::class, 'isUpcoming', $value->getId()],
                [Category::class, 'lowInventoryThreshold', $value->getId()],
                [Category::class, 'highlightLowInventory', $value->getId()],
            )
            ->willReturnOnConsecutiveCalls(null, null, null, null, $categoryId);

        $categoryRepo->expects(self::once())
            ->method('getProductIdsByCategories')
            ->with([$category])
            ->willReturn([$productId]);

        $this->changedEntities->expects(self::once())
            ->method('addEntity')
            ->with($product);

        $this->listener->postUpdate($value, new LifecycleEventArgs($value, $em));
    }

    public function testOnConfigUpdateWhenApplicableConfigOptionWasNotChanged(): void
    {
        $this->productReindexManager->expects(self::never())
            ->method('reindexAllProducts');

        $args = new ConfigUpdateEvent(
            new ConfigChangeSet(['option1' => ['new' => true, 'old' => false]]),
            'organization',
            1
        );
        $this->listener->onConfigUpdate($args);
    }

    /**
     * @dataProvider onConfigUpdateWhenApplicableConfigOptionWasChangedDataProvider
     */
    public function testOnConfigUpdateWhenApplicableConfigOptionWasChanged(string $optionName): void
    {
        $this->productReindexManager->expects(self::once())
            ->method('reindexAllProducts')
            ->with(self::isNull());

        $args = new ConfigUpdateEvent(
            new ConfigChangeSet([$optionName => ['new' => true, 'old' => false]]),
            'organization',
            1
        );
        $this->listener->onConfigUpdate($args);
    }

    /**
     * @dataProvider onConfigUpdateWhenApplicableConfigOptionWasChangedDataProvider
     */
    public function testOnConfigUpdateWhenApplicableConfigOptionWasChangedInWebsiteScope(string $optionName): void
    {
        $websiteId = 1;

        $this->productReindexManager->expects(self::once())
            ->method('reindexAllProducts')
            ->with(self::identicalTo($websiteId), true, ['inventory']);

        $args = new ConfigUpdateEvent(
            new ConfigChangeSet([$optionName => ['new' => true, 'old' => false]]),
            'website',
            $websiteId
        );
        $this->listener->onConfigUpdate($args);
    }

    public function onConfigUpdateWhenApplicableConfigOptionWasChangedDataProvider(): array
    {
        return [
            ['oro_inventory.low_inventory_threshold'],
            ['oro_inventory.highlight_low_inventory']
        ];
    }
}
