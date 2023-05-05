<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\EventListener\CategoryContentVariantIndexListener;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\CatalogBundle\Tests\Unit\ContentVariantType\Stub\ContentVariantStub;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ContentNodeStub;
use Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryContentVariantIndexListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductIndexScheduler|\PHPUnit\Framework\MockObject\MockObject */
    private $indexScheduler;

    /** @var CategoryContentVariantIndexListener */
    private $listener;

    /** @var FieldUpdatesChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldUpdatesChecker;

    /** @var WebCatalogUsageProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    protected function setUp(): void
    {
        $this->indexScheduler = $this->createMock(ProductIndexScheduler::class);
        $this->fieldUpdatesChecker = $this->createMock(FieldUpdatesChecker::class);
        $this->provider = $this->createMock(WebCatalogUsageProviderInterface::class);

        $this->listener = new CategoryContentVariantIndexListener(
            $this->indexScheduler,
            PropertyAccess::createPropertyAccessor(),
            $this->fieldUpdatesChecker,
            $this->provider
        );
    }

    public function testOnFlushNoEntities()
    {
        $entityManager = $this->getEntityManager();

        $this->indexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushNoVariants()
    {
        $this->provider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $entityManager = $this->getEntityManager(
            [new \stdClass()],
            [new \stdClass()],
            [new \stdClass()]
        );
        $this->indexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushWithCategoriesWithoutChangeSet()
    {
        $emptyCategoryVariant = $this->generateContentVariant(null, 1);
        $firstVariant = $this->generateContentVariant(1, 1);
        $secondVariant = $this->generateContentVariant(1, 1);
        $thirdVariant = $this->generateContentVariant(2, 1);
        $incorrectTypeVariant = new ContentVariantStub();
        $incorrectTypeVariant->setType('incorrectType');
        $incorrectTypeVariant->setNode($firstVariant->getNode());

        $entityManager = $this->getEntityManager(
            [new \stdClass(), $emptyCategoryVariant, $firstVariant],
            [new \stdClass(), $secondVariant],
            [new \stdClass(), $thirdVariant, $incorrectTypeVariant, $this->getCategory(3)]
        );
        $this->provider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 1]);

        $expectedCategories = [
            1 => $firstVariant->getCategoryPageCategory(),
            2 => $thirdVariant->getCategoryPageCategory()
        ];
        $this->assertCategoriesReindexationScheduled($expectedCategories, [1]);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushWithCategoriesWithChangeSet()
    {
        $oldCategory = $this->getCategory(1);
        $variant = $this->generateContentVariant(2, 1);

        $entityManager = $this->getEntityManager(
            [],
            [$variant],
            [],
            ['category_page_category' => [$oldCategory, $variant->getCategoryPageCategory()]]
        );
        $this->provider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 1]);
        $this->fieldUpdatesChecker->expects($this->never())
            ->method('isRelationFieldChanged');

        $expectedCategories = [1 => $oldCategory, 2 => $variant->getCategoryPageCategory()];
        $this->assertCategoriesReindexationScheduled($expectedCategories, [1]);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testProductsOfRelatedContentVariantWillBeReindexOnlyIfConfigurableFieldsHaveSomeChanges()
    {
        $contentVariant1 = $this->generateContentVariant(1, 1);
        $contentVariant2 = $this->generateContentVariant(2, 1);
        $contentVariant3 = $this->generateContentVariant(3, 1);
        $contentVariant4 = $this->generateContentVariant(4, 1);
        $contentVariant5 = $this->generateContentVariant(5, 1);
        $contentVariant6 = $this->generateContentVariant(6, 1);

        $contentNodeWithFieldChanges = (new ContentNodeStub(1))
            ->addContentVariant($contentVariant1)
            ->addContentVariant($contentVariant2)
            ->addContentVariant($contentVariant3);
        $contentNodeWithoutFieldChanges = (new ContentNodeStub(2))
            ->addContentVariant($contentVariant4)
            ->addContentVariant($contentVariant5)
            ->addContentVariant($contentVariant6);

        $entityManager = $this->getEntityManager(
            [],
            [$contentNodeWithFieldChanges, $contentNodeWithoutFieldChanges, new \stdClass()]
        );
        $this->provider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([0 => '1']);

        $this->fieldUpdatesChecker->expects($this->atLeastOnce())
            ->method('isRelationFieldChanged')
            ->willReturnMap([
                [$contentNodeWithFieldChanges, 'titles', true],
                [$contentNodeWithoutFieldChanges, 'titles', false],
            ]);

        // only products related to categories from $contentNodeWithFieldChanges should be reindex
        $expectedCategories = [
            $contentVariant1->getCategoryPageCategory()->getId() => $contentVariant1->getCategoryPageCategory(),
            $contentVariant2->getCategoryPageCategory()->getId() => $contentVariant2->getCategoryPageCategory(),
            $contentVariant3->getCategoryPageCategory()->getId() => $contentVariant3->getCategoryPageCategory(),
        ];

        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with($expectedCategories, null, true);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushWithCategoryAndDefaultWebsite()
    {
        $contentVariant = $this->generateContentVariant(1, 1);
        $entityManager = $this->getEntityManager([$contentVariant]);
        $this->provider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([0 => '1']);

        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([1 => $contentVariant->getCategoryPageCategory()], null, true);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushWithCategoryAndRelatedWebsite()
    {
        $contentVariant = $this->generateContentVariant(1, 1);
        $entityManager = $this->getEntityManager([$contentVariant]);
        $this->provider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => '1']);

        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([1 => $contentVariant->getCategoryPageCategory()], 1, true);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testWebsiteIdsWillNotBeCollectedIfThereAreNoProductsToReindex()
    {
        $entityManager = $this->getEntityManager();

        $this->provider->expects($this->never())
            ->method('getAssignedWebCatalogs');
        $this->indexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testReindexationWillNotBeTriggeredWhenThereAreNoWebsitesWithCurrentWebCatalog()
    {
        $contentVariant = $this->generateContentVariant(1, 1);
        $entityManager = $this->getEntityManager([$contentVariant]);

        $this->provider->expects($this->once())
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);
        $this->indexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testReindexationWillBeScheduledForAllAssignedWebsites()
    {
        $contentVariantWithCategory1 = $this->generateContentVariant(1, 1);
        $contentVariantWithCategory2 = $this->generateContentVariant(2, 1);
        $category1 = $contentVariantWithCategory1->getCategoryPageCategory();
        $category2 = $contentVariantWithCategory2->getCategoryPageCategory();
        $entityManager = $this->getEntityManager(
            [$contentVariantWithCategory1, $contentVariantWithCategory2]
        );

        $this->provider->expects($this->atLeastOnce())
            ->method('getAssignedWebCatalogs')
            ->willReturn([
                1 => 1,
                2 => 1,
                3 => 2,
            ]);
        $this->assertCategoriesReindexationScheduled([
            $category1->getId() => $category1,
            $category2->getId() => $category2,
        ], [1, 2]);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    /**
     * @dataProvider dataProviderForNotWebCatalogAwareEntities
     */
    public function testReindexationWillNotBeTriggeredWhenThereAreNotWebCatalogAwareEntitiesChanged(
        ContentVariantInterface $contentVariant
    ) {
        $entityManager = $this->getEntityManager([$contentVariant]);

        $this->provider->expects($this->once())
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => 1]);
        $this->indexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function dataProviderForNotWebCatalogAwareEntities(): array
    {
        $notContentNodeAwareEntity = $this->createMock(ContentVariantInterface::class);
        $notWebCatalogAwareEntity = $this->generateContentVariant(1, 1);
        $notWebCatalogAwareEntity->setNode($this->createMock(ContentNodeInterface::class));

        return [
            [$notContentNodeAwareEntity],
            [$notWebCatalogAwareEntity],
        ];
    }

    /**
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEntityManager(
        array $insertions = [],
        array $updates = [],
        array $deletions = [],
        array $entityChangeSet = []
    ) {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertions);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $unitOfWork->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);
        $unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn($entityChangeSet);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $entityManager;
    }

    private function getCategory(int $id = null): Category
    {
        $category = new Category();
        ReflectionUtil::setId($category, $id);

        return $category;
    }

    private function generateContentVariant(int $categoryId = null, int $webCatalogId = 1): ContentVariantStub
    {
        $node = null;
        if ($webCatalogId) {
            $webCatalogMock = $this->createMock(WebCatalogInterface::class);
            $webCatalogMock->expects($this->any())
                ->method('getId')
                ->willReturn($webCatalogId);
            $node = (new ContentNodeStub(1))->setWebCatalog($webCatalogMock);
        }

        $contentVariant = new ContentVariantStub();
        $contentVariant->setCategoryPageCategory($this->getCategory($categoryId));
        $contentVariant->setType(CategoryPageContentVariantType::TYPE);
        $contentVariant->setNode($node);

        return $contentVariant;
    }

    private function assertCategoriesReindexationScheduled(array $categories, array $websiteIds)
    {
        $arguments = [];
        foreach ($websiteIds as $websiteId) {
            $arguments[] = [$categories, $websiteId, true];
        }

        $this->indexScheduler->expects($this->exactly(count($websiteIds)))
            ->method('scheduleProductsReindex')
            ->withConsecutive(...$arguments);
    }
}
