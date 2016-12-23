<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
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

    protected function setUp()
    {
        $this->indexScheduler = $this->getMockBuilder(ProductIndexScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->accessor = PropertyAccess::createPropertyAccessor();

        $this->listener = new CategoryContentVariantIndexListener($this->indexScheduler, $this->accessor);
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

    public function testOnFormAfterFlushNotNode()
    {
        $entity = new \stdClass();
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);

        $this->indexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');

        $this->listener->onFormAfterFlush(new AfterFormProcessEvent($form, $entity));
    }

    public function testOnFormAfterFlushWithoutCategoryVariants()
    {
        $incorrectTypeVariant = $this->getEntity(ContentVariantStub::class, ['type' => 'incorrectType']);

        $node = $this->createMock(ContentNodeInterface::class);
        $node->expects($this->any())
            ->method('getContentVariants')
            ->willReturn(new ArrayCollection([$incorrectTypeVariant]));

        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);

        $this->indexScheduler->expects($this->never())
            ->method('scheduleProductsReindex');

        $this->listener->onFormAfterFlush(new AfterFormProcessEvent($form, $node));
    }

    public function testOnFormAfterFlushWithCategoryVariants()
    {
        $emptyCategory = $this->getEntity(Category::class);
        $firstCategory = $this->getEntity(Category::class, ['id' => 1]);
        $secondCategory = $this->getEntity(Category::class, ['id' => 2]);

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
        $incorrectTypeVariant = $this->getEntity(
            ContentVariantStub::class,
            ['type' => 'incorrectType']
        );

        $node = $this->createMock(ContentNodeInterface::class);
        $node->expects($this->any())
            ->method('getContentVariants')
            ->willReturn(new ArrayCollection(
                [$emptyCategoryVariant, $firstVariant, $secondVariant, $incorrectTypeVariant]
            ));

        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);

        $this->indexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([1 => $firstCategory, 2 => $secondCategory], null, true);

        $this->listener->onFormAfterFlush(new AfterFormProcessEvent($form, $node));
    }
}
