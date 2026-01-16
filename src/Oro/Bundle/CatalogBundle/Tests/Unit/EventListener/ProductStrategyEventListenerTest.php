<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\AbstractProductImportEventListener;
use Oro\Bundle\CatalogBundle\EventListener\ProductStrategyEventListener;
use Oro\Bundle\CatalogBundle\ImportExport\Mapper\CategoryPathMapper;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Field\FieldHeaderHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ProductStrategyEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private ManagerRegistry|MockObject $registry;
    private AclHelper|MockObject $aclHelper;
    private ConfigManager|MockObject $configManager;
    private MasterCatalogRootProviderInterface|MockObject $masterCatalogRootProvider;
    private FieldHeaderHelper|MockObject $fieldHeaderHelper;
    private FieldHelper|MockObject $fieldHelper;
    private CategoryPathMapper $categoryPathMapper;
    private CategoryRepository|MockObject $categoryRepository;
    private ProductStrategyEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->masterCatalogRootProvider = $this->createMock(MasterCatalogRootProviderInterface::class);
        $this->fieldHeaderHelper = $this->createMock(FieldHeaderHelper::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->categoryPathMapper = new CategoryPathMapper();
        $this->categoryRepository = $this->createMock(CategoryRepository::class);

        $this->listener = new ProductStrategyEventListener($this->registry, $this->aclHelper, Category::class);
    }

    /**
     * Tests for OLD behavior (when dependencies are not set)
     */
    public function testOnProcessAfterWithoutCategoryKey(): void
    {
        $product = new Product();
        $event = new ProductStrategyEvent($product, []);
        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->listener->onProcessAfter($event);
    }

    public function testOnProcessAfterWithoutCategory(): void
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

    public function testOnProcessAfter(): void
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

    /**
     * Helper methods for new behavior tests
     */
    private function setupNewBehavior(): void
    {
        $this->listener->setConfigManager($this->configManager);
        $this->listener->setMasterCatalogRootProvider($this->masterCatalogRootProvider);
        $this->listener->setFieldHeaderHelper($this->fieldHeaderHelper);
        $this->listener->setFieldHelper($this->fieldHelper);
        $this->listener->setAclHelper($this->aclHelper);
        $this->listener->setCategoryPathMapper($this->categoryPathMapper);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        // Mock fieldHeaderHelper to return 'Category.ID' for category ID column
        $this->fieldHeaderHelper->expects($this->any())
            ->method('buildRelationFieldHeader')
            ->willReturn('Category.ID');

        // Mock fieldHelper to return false for category field excluded check (meaning ID is exported)
        $this->fieldHelper->expects($this->any())
            ->method('getConfigValue')
            ->willReturn(false);
    }

    private function createCategory(int $id, ?string $title = null): CategoryStub
    {
        $category = new CategoryStub($id);

        if ($title !== null) {
            $category->addTitle((new CategoryTitle())->setString($title));
        }

        return $category;
    }

    /**
     * Tests for NEW behavior - Early returns
     */

    /**
     * Test (1): Category by ID exists and ID wins strategy - should return early
     */
    public function testIdWinsStrategyReturnsEarly(): void
    {
        $this->setupNewBehavior();

        $categoryById = $this->createCategory(1);
        $product = new Product();
        $product->setCategory($categoryById);

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'Some Title',
        ];
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION))
            ->willReturn(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_ID_WINS);

        // Should not search for category by title
        $this->categoryRepository->expects($this->never())
            ->method('findByDefaultTitleQueryBuilder');

        $this->listener->onProcessAfter($event);

        // Category should remain unchanged
        $this->assertSame($categoryById, $product->getCategory());
    }

    /**
     * Test (2): Category by ID exists, no title/path provided - should return early
     */
    public function testCategoryByIdExistsNoTitleOrPath(): void
    {
        $this->setupNewBehavior();

        $categoryById = $this->createCategory(1);
        $product = new Product();
        $product->setCategory($categoryById);

        $rawData = []; // No title, no path
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION))
            ->willReturn(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS);

        // Should not search for category
        $this->categoryRepository->expects($this->never())
            ->method('findByDefaultTitleQueryBuilder');

        $this->listener->onProcessAfter($event);

        // Category should remain unchanged
        $this->assertSame($categoryById, $product->getCategory());
    }

    /**
     * Test (3): No category ID, no title, no path - should return early
     */
    public function testNoCategoryNoTitleNoPath(): void
    {
        $this->setupNewBehavior();

        $product = new Product();
        $rawData = []; // No title, no path
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS
                ],
            ]);

        // Should not search for category
        $this->categoryRepository->expects($this->never())
            ->method('findByDefaultTitleQueryBuilder');

        $this->listener->onProcessAfter($event);

        // Category should remain null
        $this->assertNull($product->getCategory());
    }

    /**
     * Tests for category search by path
     */

    /**
     * Search by path - first match strategy (no category by ID, found by path)
     * Corresponds to scenario (6) in the implementation
     */
    public function testSearchByPathFirstMatch(): void
    {
        $this->setupNewBehavior();

        $product = new Product();
        $categoryByPath = $this->createCategory(1);
        $masterCatalogRoot = $this->createCategory(100);

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_PATH_KEY => 'All Products / Electronics',
        ];
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FIRST
                ],
            ]);

        $this->masterCatalogRootProvider->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($masterCatalogRoot);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $this->categoryRepository->expects($this->once())
            ->method('findByTitlesPathQueryBuilder')
            ->with(['All Products', 'Electronics'], $masterCatalogRoot)
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($categoryByPath);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->listener->onProcessAfter($event);

        $this->assertSame($categoryByPath, $product->getCategory());
    }

    /**
     * Search by path - fail on non-unique
     * Corresponds to scenario (4) in the implementation
     */
    public function testSearchByPathFailOnNonUnique(): void
    {
        $this->setupNewBehavior();

        $product = new Product();
        $masterCatalogRoot = $this->createCategory(100);

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_PATH_KEY => 'All Products / Accessories',
        ];
        $context = new Context([]);
        $event = new ProductStrategyEvent($product, $rawData);
        $event->setContext($context);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FAIL
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_PATH),
                    false,
                    false,
                    null,
                    false
                ],
            ]);

        $this->masterCatalogRootProvider->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($masterCatalogRoot);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->categoryRepository->expects($this->once())
            ->method('findByTitlesPathQueryBuilder')
            ->with(['All Products', 'Accessories'], $masterCatalogRoot)
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willThrowException(new NonUniqueResultException());

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->listener->onProcessAfter($event);

        // Product should be marked invalid
        $this->assertFalse($event->isProductValid());
        $this->assertEquals(1, $context->getErrorEntriesCount());

        $errors = $context->getErrors();
        $this->assertCount(1, $errors);
        // EXPORT_CATEGORY_PATH is false, so no path suggestion
        // suggestId is false (searching by path, not by title when no category by ID)
        // But isCategoryIdExported() is true, so ID suggestion is included
        $this->assertEquals(
            'Category "All Products / Accessories" is not unique in the master catalog. '
            . 'Specify the correct category ID in the "Category.ID" column.',
            $errors[0]
        );
    }

    /**
     * Search by title - first match strategy (no category by ID, found by title)
     * Corresponds to scenario (6) in the implementation
     */
    public function testSearchByTitleFirstMatch(): void
    {
        $this->setupNewBehavior();

        $product = new Product();
        $categoryByTitle = $this->createCategory(1);

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'Electronics',
        ];
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FIRST
                ],
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $this->categoryRepository->expects($this->once())
            ->method('findByDefaultTitleQueryBuilder')
            ->with('Electronics')
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($categoryByTitle);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->listener->onProcessAfter($event);

        $this->assertSame($categoryByTitle, $product->getCategory());
    }

    /**
     * Search by title - fail on non-unique
     * Corresponds to scenario (4) in the implementation
     */
    public function testSearchByTitleFailOnNonUnique(): void
    {
        $this->setupNewBehavior();

        $product = new Product();

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'Accessories',
        ];
        $context = new Context([]);
        $event = new ProductStrategyEvent($product, $rawData);
        $event->setContext($context);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FAIL
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_PATH),
                    false,
                    false,
                    null,
                    true
                ],
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->categoryRepository->expects($this->once())
            ->method('findByDefaultTitleQueryBuilder')
            ->with('Accessories')
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willThrowException(new NonUniqueResultException());

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->listener->onProcessAfter($event);

        // Product should be marked invalid
        $this->assertFalse($event->isProductValid());
        $this->assertEquals(1, $context->getErrorEntriesCount());

        $errors = $context->getErrors();
        $this->assertCount(1, $errors);
        // EXPORT_CATEGORY_PATH is true, so path suggestion is included
        // isCategoryIdExported() is true, so ID suggestion is also included (with "or specify")
        $this->assertEquals(
            'Category "Accessories" is not unique in the master catalog. '
            . 'Specify the full category path, like "All Products > Supplies > Parts" '
            . 'in the "category.path" column to uniquely identify the category, '
            . 'or specify the correct category ID in the "Category.ID" column.',
            $errors[0]
        );
    }

    /**
     * Category not found by title/path
     * Corresponds to scenario (5) in the implementation
     */
    public function testCategoryNotFound(): void
    {
        $this->setupNewBehavior();

        $product = new Product();

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'NonExistent',
        ];
        $context = new Context([]);
        $event = new ProductStrategyEvent($product, $rawData);
        $event->setContext($context);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FIRST
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_PATH),
                    false,
                    false,
                    null,
                    false
                ],
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $this->categoryRepository->expects($this->once())
            ->method('findByDefaultTitleQueryBuilder')
            ->with('NonExistent')
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->listener->onProcessAfter($event);

        // Product should be marked invalid
        $this->assertFalse($event->isProductValid());
        $this->assertEquals(1, $context->getErrorEntriesCount());

        $errors = $context->getErrors();
        $this->assertCount(1, $errors);
        // EXPORT_CATEGORY_PATH is false, suggestId is true (no category by ID), so ID suggestion is included
        $this->assertEquals(
            'Category "NonExistent" not found in the master catalog. '
            . 'Specify the correct category ID in the "Category.ID" column.',
            $errors[0]
        );
    }

    /**
     * No category by ID, but found by title - should set category
     * Corresponds to scenario (6) in the implementation
     */
    public function testNoCategoryByIdButFoundByTitle(): void
    {
        $this->setupNewBehavior();

        $product = new Product();
        $categoryByTitle = $this->createCategory(1);

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'Electronics',
        ];
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FIRST
                ],
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $this->categoryRepository->expects($this->once())
            ->method('findByDefaultTitleQueryBuilder')
            ->with('Electronics')
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($categoryByTitle);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->listener->onProcessAfter($event);

        $this->assertSame($categoryByTitle, $product->getCategory());
    }

    /**
     * Category by ID and title match - should do nothing
     * Corresponds to scenario (7) in the implementation
     */
    public function testCategoryByIdAndTitleMatch(): void
    {
        $this->setupNewBehavior();

        $category = $this->createCategory(1);
        $product = new Product();
        $product->setCategory($category);

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'Electronics',
        ];
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FIRST
                ],
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $this->categoryRepository->expects($this->once())
            ->method('findByDefaultTitleQueryBuilder')
            ->with('Electronics')
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($category); // Same category

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->listener->onProcessAfter($event);

        // Category should remain the same
        $this->assertSame($category, $product->getCategory());
    }

    /**
     * Mismatch - ID wins strategy
     * Corresponds to scenario (1) in the implementation
     */
    public function testMismatchIdWins(): void
    {
        $this->setupNewBehavior();

        $categoryById = $this->createCategory(1);
        $product = new Product();
        $product->setCategory($categoryById);

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'Electronics',
        ];
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_ID_WINS
                ],
            ]);

        // Should not search for category when ID wins
        $this->categoryRepository->expects($this->never())
            ->method('findByDefaultTitleQueryBuilder');

        $this->listener->onProcessAfter($event);

        // Category by ID should win (remain unchanged)
        $this->assertSame($categoryById, $product->getCategory());
    }

    /**
     * Mismatch - path/title wins strategy
     * Corresponds to scenario (8) in the implementation
     */
    public function testMismatchPathOrTitleWins(): void
    {
        $this->setupNewBehavior();

        $categoryById = $this->createCategory(1, 'Accessories');
        $categoryByTitle = $this->createCategory(2, 'Electronics');
        $product = new Product();
        $product->setCategory($categoryById);

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'Electronics',
        ];
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FIRST
                ],
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $this->categoryRepository->expects($this->once())
            ->method('findByDefaultTitleQueryBuilder')
            ->with('Electronics')
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($categoryByTitle);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->listener->onProcessAfter($event);

        // Category by title should win
        $this->assertSame($categoryByTitle, $product->getCategory());
    }

    /**
     * Mismatch - fail strategy
     * Corresponds to scenario (9) in the implementation
     */
    public function testMismatchFail(): void
    {
        $this->setupNewBehavior();

        $categoryById = $this->createCategory(1, 'Accessories');
        $categoryByTitle = $this->createCategory(2, 'Electronics');
        $product = new Product();
        $product->setCategory($categoryById);

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'Electronics',
        ];
        $context = new Context([]);
        $event = new ProductStrategyEvent($product, $rawData);
        $event->setContext($context);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_FAIL
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FIRST
                ],
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $this->categoryRepository->expects($this->once())
            ->method('findByDefaultTitleQueryBuilder')
            ->with('Electronics')
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($categoryByTitle);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->listener->onProcessAfter($event);

        // Product should be marked invalid
        $this->assertFalse($event->isProductValid());
        $this->assertEquals(1, $context->getErrorEntriesCount());

        $errors = $context->getErrors();
        $this->assertCount(1, $errors);
        // suggestPath is true (no path provided), suggestId is false (category by ID exists)
        // EXPORT_CATEGORY_PATH is not configured in this test, so it defaults to false
        $this->assertEquals(
            'Category title "Electronics" does not match the category with ID 1 ("Accessories").',
            $errors[0]
        );
    }

    public function testOnClear(): void
    {
        $this->setupNewBehavior();

        // Call onClear to ensure it doesn't throw errors
        $this->listener->onClear();

        // No assertions needed - just ensuring no exceptions
        $this->assertTrue(true);
    }

    /**
     * Test unknown non-unique resolution throws LogicException
     */
    public function testUnknownNonUniqueResolutionThrowsException(): void
    {
        $this->setupNewBehavior();

        $product = new Product();
        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'Electronics',
        ];
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION_PATH_OR_TITLE_WINS
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    'invalid_resolution_value' // Invalid value
                ],
            ]);

        $masterCatalogRoot = $this->createCategory(999, 'All Products');
        $this->masterCatalogRootProvider->method('getMasterCatalogRoot')
            ->willReturn($masterCatalogRoot);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->categoryRepository->method('findByDefaultTitleQueryBuilder')
            ->with('Electronics')
            ->willReturn($queryBuilder);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown category non-unique title resolution: invalid_resolution_value.');

        $this->listener->onProcessAfter($event);
    }

    /**
     * Test unknown mismatch resolution throws LogicException
     */
    public function testUnknownMismatchResolutionThrowsException(): void
    {
        $this->setupNewBehavior();

        $categoryById = $this->createCategory(1, 'Accessories');
        $categoryByTitle = $this->createCategory(2, 'Electronics');
        $product = new Product();
        $product->setCategory($categoryById);

        $rawData = [
            AbstractProductImportEventListener::CATEGORY_KEY => 'Electronics',
        ];
        $event = new ProductStrategyEvent($product, $rawData);

        $this->configManager->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_MISMATCH_RESOLUTION),
                    false,
                    false,
                    null,
                    'invalid_mismatch_value' // Invalid value
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION),
                    false,
                    false,
                    null,
                    Configuration::IMPORT_CATEGORY_NON_UNIQUE_RESOLUTION_FIRST
                ],
            ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $this->categoryRepository->expects($this->once())
            ->method('findByDefaultTitleQueryBuilder')
            ->with('Electronics')
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($categoryByTitle);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown category ID/title mismatch resolution: invalid_mismatch_value.');

        $this->listener->onProcessAfter($event);
    }
}
