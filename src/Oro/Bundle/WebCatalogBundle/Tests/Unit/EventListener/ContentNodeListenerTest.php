<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Handler\CollectionSortOrderHandler;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\EventListener\ContentNodeListener;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Test\FormInterface;

class ContentNodeListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private ContentNodeMaterializedPathModifier|\PHPUnit\Framework\MockObject\MockObject $modifier;

    private ExtraActionEntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject $storage;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private ResolveNodeSlugsMessageFactory|\PHPUnit\Framework\MockObject\MockObject $messageFactory;

    private ContentNodeListener $contentNodeListener;

    protected function setUp(): void
    {
        $this->modifier = $this->createMock(ContentNodeMaterializedPathModifier::class);
        $this->storage = $this->createMock(ExtraActionEntityStorageInterface::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->createMock(ResolveNodeSlugsMessageFactory::class);
        $this->collectionSortOrderHandler = $this->createMock(CollectionSortOrderHandler::class);

        $this->contentNodeListener = new ContentNodeListener(
            $this->modifier,
            $this->storage,
            $this->messageProducer,
            $this->messageFactory,
            $this->collectionSortOrderHandler
        );
    }

    public function testPostPersist(): void
    {
        $contentNode = new ContentNode();

        $this->modifier->expects(self::once())
            ->method('calculateMaterializedPath')
            ->with($contentNode);

        $this->contentNodeListener->postPersist($contentNode);
    }

    public function testPreUpdate(): void
    {
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 42]);

        $args = $this->createMock(PreUpdateEventArgs::class);

        $args->expects(self::once())
            ->method('getEntityChangeSet')
            ->willReturn([ContentNode::FIELD_PARENT_NODE => [null, new ContentNode()]]);

        $childNode = new ContentNode();

        $this->modifier->expects(self::once())
            ->method('calculateChildrenMaterializedPath')
            ->with($contentNode)
            ->willReturn([new ContentNode()]);

        $this->storage->expects(self::exactly(2))
            ->method('scheduleForExtraInsert')
            ->withConsecutive([$contentNode], [$childNode]);

        $this->contentNodeListener->preUpdate($contentNode, $args);
    }

    public function testOnFormAfterFlushWithoutSortOrder(): void
    {
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        $this->messageFactory->expects(self::once())
            ->method('createMessage')
            ->with($contentNode)
            ->willReturn([]);
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(WebCatalogResolveContentNodeSlugsTopic::getName(), []);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('get')
            ->with('contentVariants')
            ->willReturn([]);
        $event = $this->createMock(AfterFormProcessEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $this->collectionSortOrderHandler->expects(self::once())
            ->method('updateCollections')
            ->with([]);
        $event->expects(self::once())
            ->method('getData')
            ->willReturn($contentNode);

        $this->contentNodeListener->onFormAfterFlush($event);
    }

    public function testOnFormAfterFlushWithSortOrder(): void
    {
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        $this->messageFactory->expects(self::once())
            ->method('createMessage')
            ->with($contentNode)
            ->willReturn([]);
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(WebCatalogResolveContentNodeSlugsTopic::getName(), []);

        $segment = $this->createMock(Segment::class);
        $segment->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);
        $collectionSortOrderForm = $this->createMock(FormInterface::class);
        $collectionSortOrder = $this->createMock(CollectionSortOrder::class);
        $collectionSortOrderForm->expects($this->exactly(2))
            ->method('getData')
            ->willReturn([['data' => $collectionSortOrder]]);
        $productCollectionSegmentForm = $this->createMock(FormInterface::class);
        $productCollectionSegmentForm->expects($this->once())
            ->method('get')
            ->with('sortOrder')
            ->willReturn($collectionSortOrderForm);
        $productCollectionSegmentForm->expects($this->once())
            ->method('has')
            ->with('sortOrder')
            ->willReturn(true);
        $productCollectionSegmentForm->expects($this->once())
            ->method('getData')
            ->willReturn($segment);
        $contentVariantForm = $this->createMock(FormInterface::class);
        $contentVariantForm->expects($this->exactly(2))
            ->method('get')
            ->with('productCollectionSegment')
            ->willReturn($productCollectionSegmentForm);
        $contentVariantForm->expects($this->once())
            ->method('has')
            ->with('productCollectionSegment')
            ->willReturn(true);
        $contentNodeForm = $this->createMock(FormInterface::class);
        $contentNodeForm->expects($this->once())
            ->method('get')
            ->with('contentVariants')
            ->willReturn([$contentVariantForm]);
        $event = $this->createMock(AfterFormProcessEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($contentNodeForm);

        $this->collectionSortOrderHandler->expects(self::once())
            ->method('updateCollections')
            ->with([1 => ['segment' => $segment, 'sortOrders' => [0 => $collectionSortOrder]]]);

        $event->expects(self::once())
            ->method('getData')
            ->willReturn($contentNode);

        $this->contentNodeListener->onFormAfterFlush($event);
    }

    public function testPostRemove(): void
    {
        /** @var ContentNode $parentNode */
        $parentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 2, 'parentNode' => $parentNode]);

        $this->messageFactory->expects(self::once())
            ->method('createMessage')
            ->with($parentNode)
            ->willReturn([
                WebCatalogResolveContentNodeSlugsTopic::ID => $parentNode->getId(),
                WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
            ]);
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                WebCatalogResolveContentNodeSlugsTopic::getName(),
                [
                    WebCatalogResolveContentNodeSlugsTopic::ID => $parentNode->getId(),
                    WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
                ]
            );

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects(self::once())
            ->method('isScheduledForDelete')
            ->with($parentNode)
            ->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($em);

        $this->contentNodeListener->postRemove($contentNode, $event);
    }

    public function testPostRemoveNoParent(): void
    {
        $parentNode = null;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 42]);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(
            ContentNode::class,
            ['id' => 2, 'parentNode' => $parentNode, 'webCatalog' => $webCatalog]
        );

        $this->messageFactory->expects(self::never())
            ->method('createMessage');
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(WebCatalogCalculateCacheTopic::getName(), [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 42]);
        $event = $this->createMock(LifecycleEventArgs::class);

        $this->contentNodeListener->postRemove($contentNode, $event);
    }

    public function testPostRemoveParentRemoved(): void
    {
        /** @var ContentNode $parentNode */
        $parentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 2, 'parentNode' => $parentNode]);

        $this->messageFactory->expects(self::never())
            ->method('createMessage');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects(self::once())
            ->method('isScheduledForDelete')
            ->with($parentNode)
            ->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($em);

        $this->contentNodeListener->postRemove($contentNode, $event);
    }
}
