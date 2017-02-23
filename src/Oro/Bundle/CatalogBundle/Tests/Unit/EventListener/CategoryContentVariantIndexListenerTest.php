<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;

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

    protected function setUp()
    {
        $this->indexScheduler = $this->getMockBuilder(ProductIndexScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldUpdatesChecker = $this->getMockBuilder(FieldUpdatesChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->accessor = PropertyAccess::createPropertyAccessor();

        $this->listener = new CategoryContentVariantIndexListener(
            $this->indexScheduler,
            $this->accessor,
            $this->fieldUpdatesChecker
        );
    }

    public function testOnFlushNoEntities()
    {
        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->indexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushNoVariants()
    {
        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new \stdClass()]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new \stdClass()]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([new \stdClass()]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->indexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushWithCategoriesWithoutChangeSet()
    {
        $emptyCategory = $this->getEntity(Category::class);
        $firstCategory = $this->getEntity(Category::class, ['id' => 1]);
        $secondCategory = $this->getEntity(Category::class, ['id' => 2]);
        $thirdCategory = $this->getEntity(Category::class, ['id' => 3]);

        $firstEntity = new \stdClass();
        $secondEntity = new \stdClass();
        $thirdEntity = new \stdClass();

        $emptyCategoryVariant = $this->getEntity(
            ContentVariantStub::class,
            ['categoryPageCategory' => $emptyCategory, 'type' => CategoryPageContentVariantType::TYPE]
        );
        $firstVariant = $this->getEntity(
            ContentVariantStub::class,
            ['categoryPageCategory' => $firstCategory, 'type' => CategoryPageContentVariantType::TYPE]
        );
        $secondVariant = $this->getEntity(
            ContentVariantStub::class,
            ['categoryPageCategory' => $secondCategory, 'type' => CategoryPageContentVariantType::TYPE]
        );
        $thirdVariant = $this->getEntity(
            ContentVariantStub::class,
            ['categoryPageCategory' => $firstCategory, 'type' => CategoryPageContentVariantType::TYPE]
        );
        $incorrectTypeVariant = $this->getEntity(
            ContentVariantStub::class,
            ['type' => 'incorrectType']
        );

        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$firstEntity, $emptyCategoryVariant, $firstVariant]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$secondEntity, $secondVariant]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$thirdEntity, $thirdVariant, $incorrectTypeVariant, $thirdCategory]);
        $unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn([]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([1 => $firstCategory, 2 => $secondCategory], null, true);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushWithCategoriesWithChangeSet()
    {
        $oldCategory = $this->getEntity(Category::class, ['id' => 1]);
        $newCategory = $this->getEntity(Category::class, ['id' => 2]);

        $variant = $this->getEntity(
            ContentVariantStub::class,
            ['categoryPageCategory' => $newCategory, 'type' => CategoryPageContentVariantType::TYPE]
        );

        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$variant]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($variant)
            ->willReturn(['category_page_category' => [$oldCategory, $newCategory]]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([1 => $oldCategory, 2 => $newCategory], null, true);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testOnFlushWithCategoriesWithEmptyChangeSet()
    {
        $category = $this->getEntity(Category::class, ['id' => 1]);

        $variant = $this->getEntity(
            ContentVariantStub::class,
            ['categoryPageCategory' => $category, 'type' => CategoryPageContentVariantType::TYPE]
        );

        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$variant]);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($variant)
            ->willReturn(['category_page_category' => [0 => null, 1 => null]]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([1 => $category], null, true);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testProductsOfRelatedContentVariantWillBeReindexOnlyIfConfigurableFieldsHaveSomeChanges()
    {
        $contentVariant1 = $this->generateContentVariant(1);
        $contentVariant2 = $this->generateContentVariant(2);
        $contentVariant3 = $this->generateContentVariant(3);
        $contentVariant4 = $this->generateContentVariant(4);
        $contentVariant5 = $this->generateContentVariant(5);
        $contentVariant6 = $this->generateContentVariant(6);

        $contentNodeWithFieldChanges = (new ContentNodeStub(1))
            ->addContentVariant($contentVariant1)
            ->addContentVariant($contentVariant2)
            ->addContentVariant($contentVariant3);
        $contentNodeWithoutFieldChanges = (new ContentNodeStub(2))
            ->addContentVariant($contentVariant4)
            ->addContentVariant($contentVariant5)
            ->addContentVariant($contentVariant6);

        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $unitOfWork
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn([$contentNodeWithFieldChanges, $contentNodeWithoutFieldChanges, new \stdClass()]);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

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

    /**
     * @param int $categoryId
     * @return ContentVariantStub
     */
    private function generateContentVariant($categoryId)
    {
        return $this->getEntity(
            ContentVariantStub::class,
            [
                'categoryPageCategory' => $this->getEntity(Category::class, ['id' => $categoryId]),
                'type' => CategoryPageContentVariantType::TYPE
            ]
        );
    }
}
