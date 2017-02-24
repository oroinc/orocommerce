<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ContentNodeStub;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Tests\Unit\ContentVariantType\Stub\ContentVariantStub;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\CatalogBundle\EventListener\CategoryContentVariantIndexListener;

class CategoryContentVariantIndexListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ProductIndexScheduler|\PHPUnit_Framework_MockObject_MockObject */
    private $indexScheduler;

    /** @var PropertyAccessorInterface */
    private $accessor;

    /** @var CategoryContentVariantIndexListener */
    private $listener;

    /** @var FieldUpdatesChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $fieldUpdatesChecker;

    /** @var WebCatalogUsageProviderInterface|\PHPUnit_Framework_MockObject_MockObject  */
    private $provider;

    protected function setUp()
    {
        $this->indexScheduler = $this->getMockBuilder(ProductIndexScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldUpdatesChecker = $this->getMockBuilder(FieldUpdatesChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->accessor = PropertyAccess::createPropertyAccessor();

        $this->provider = $this->getMockBuilder(WebCatalogUsageProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CategoryContentVariantIndexListener(
            $this->indexScheduler,
            $this->accessor,
            $this->fieldUpdatesChecker,
            $this->provider
        );
    }

    public function testOnFlushNoEntities()
    {
        $entityManager = $this->getEntityManager([], [], []);

        $this->indexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushNoVariants()
    {
        $entityManager = $this->getEntityManager([new \stdClass()], [new \stdClass()], [new \stdClass()]);
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
        $incorrectTypeVariant = $this->getEntity(
            ContentVariantStub::class,
            [
                'type' => 'incorrectType',
                'node' => $firstVariant->getNode()
            ]
        );

        $entityManager = $this->getEntityManager(
            [new \stdClass(), $emptyCategoryVariant, $firstVariant],
            [new \stdClass(), $secondVariant],
            [new \stdClass(), $thirdVariant, $incorrectTypeVariant, $this->getEntity(Category::class, ['id' => 3])]
        );
        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $unitOfWork */
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn([]);

        $this->provider
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $expectedCategories = [
            1 => $firstVariant->getCategoryPageCategory(),
            2 => $thirdVariant->getCategoryPageCategory()
        ];
        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with($expectedCategories, null, true);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushWithCategoriesWithChangeSet()
    {
        $oldCategory = $this->getEntity(Category::class, ['id' => 1]);
        $variant = $this->generateContentVariant(2, 1);

        $entityManager = $this->getEntityManager(
            [],
            [$variant],
            []
        );
        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $unitOfWork */
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($variant)
            ->willReturn(['category_page_category' => [$oldCategory, $variant->getCategoryPageCategory()]]);

        $this->provider
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);
        $this->fieldUpdatesChecker
            ->method('isRelationFieldChanged')
            ->willReturn(true);

        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([1 => $oldCategory, 2 => $variant->getCategoryPageCategory()], null, true);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushWithCategoriesWithEmptyChangeSet()
    {
        $variant = $this->generateContentVariant(1, 1);

        $entityManager = $this->getEntityManager(
            [],
            [$variant],
            []
        );
        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $unitOfWork */
        $unitOfWork = $entityManager->getUnitOfWork();

        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($variant)
            ->willReturn(['category_page_category' => [0 => null, 1 => null]]);
        $this->provider
            ->method('getAssignedWebCatalogs')
            ->willReturn([]);

        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([1 => $variant->getCategoryPageCategory()], null, true);

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
            [$contentNodeWithFieldChanges, $contentNodeWithoutFieldChanges, new \stdClass()],
            []
        );
        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $unitOfWork */
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn([]);

        $this->provider
            ->method('getAssignedWebCatalogs')
            ->willReturn([0 => '1']);

        $this->fieldUpdatesChecker
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
        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $unitOfWork */
        $unitOfWork = $entityManager->getUnitOfWork();

        $unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn([]);
        $this->provider
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
        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $unitOfWork */
        $unitOfWork = $entityManager->getUnitOfWork();

        $unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn([]);
        $this->provider
            ->method('getAssignedWebCatalogs')
            ->willReturn([1 => '1']);

        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([1 => $contentVariant->getCategoryPageCategory()], 1, true);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    /**
     * @param int  $categoryId
     * @param int|null $webCatalogId
     * @return ContentVariantStub
     */
    private function generateContentVariant($categoryId = null, $webCatalogId = null)
    {
        $node = null;
        if ($webCatalogId) {
            $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $webCatalogId]);
            $node = $this->getEntity(ContentNode::class, ['webCatalog' => $webCatalog]);
        }

        return $this->getEntity(
            ContentVariantStub::class,
            [
                'categoryPageCategory' => $this->getEntity(Category::class, ['id' => $categoryId]),
                'type' => CategoryPageContentVariantType::TYPE,
                'node' => $node
            ]
        );
    }

    /**
     * @param array $insertions
     * @param array $updates
     * @param array $deletions
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    private function getEntityManager(array $insertions = [], array $updates = [], array $deletions = [])
    {
        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertions);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $entityManager;
    }
}
