<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\EventListener\ContentNodeListener;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ContentNodeMaterializedPathModifier|\PHPUnit\Framework\MockObject\MockObject */
    private $modifier;

    /** @var ExtraActionEntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var ResolveNodeSlugsMessageFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $messageFactory;

    /** @var ContentNodeListener */
    private $contentNodeListener;

    protected function setUp(): void
    {
        $this->modifier = $this->createMock(ContentNodeMaterializedPathModifier::class);
        $this->storage = $this->createMock(ExtraActionEntityStorageInterface::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->createMock(ResolveNodeSlugsMessageFactory::class);

        $this->contentNodeListener = new ContentNodeListener(
            $this->modifier,
            $this->storage,
            $this->messageProducer,
            $this->messageFactory
        );
    }

    public function testPostPersist()
    {
        $contentNode = new ContentNode();

        $this->modifier->expects($this->once())
            ->method('calculateMaterializedPath')
            ->with($contentNode);

        $this->contentNodeListener->postPersist($contentNode);
    }

    public function testPreUpdate()
    {
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 42]);

        $args = $this->createMock(PreUpdateEventArgs::class);

        $args->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn([ContentNode::FIELD_PARENT_NODE => [null, new ContentNode()]]);

        $childNode = new ContentNode();

        $this->modifier->expects($this->once())
            ->method('calculateChildrenMaterializedPath')
            ->with($contentNode)
            ->willReturn([new ContentNode()]);

        $this->storage->expects($this->exactly(2))
            ->method('scheduleForExtraInsert')
            ->withConsecutive([$contentNode], [$childNode]);

        $this->contentNodeListener->preUpdate($contentNode, $args);
    }

    public function testOnFormAfterFlush()
    {
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        $this->messageFactory->expects($this->once())
            ->method('createMessage')
            ->with($contentNode)
            ->willReturn([]);
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::RESOLVE_NODE_SLUGS, []);

        $event = $this->createMock(AfterFormProcessEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($contentNode);

        $this->contentNodeListener->onFormAfterFlush($event);
    }

    public function testPostRemove()
    {
        /** @var ContentNode $parentNode */
        $parentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 2, 'parentNode' => $parentNode]);

        $this->messageFactory->expects($this->once())
            ->method('createMessage')
            ->with($parentNode)
            ->willReturn([
                ResolveNodeSlugsMessageFactory::ID => $parentNode->getId(),
                ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true
            ]);
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(
                Topics::RESOLVE_NODE_SLUGS,
                [
                    ResolveNodeSlugsMessageFactory::ID => $parentNode->getId(),
                    ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true
                ]
            );

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())
            ->method('isScheduledForDelete')
            ->with($parentNode)
            ->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->contentNodeListener->postRemove($contentNode, $event);
    }

    public function testPostRemoveNoParent()
    {
        $parentNode = null;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 42]);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(
            ContentNode::class,
            ['id' => 2, 'parentNode' => $parentNode, 'webCatalog' => $webCatalog]
        );

        $this->messageFactory->expects($this->never())
            ->method('createMessage');
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::CALCULATE_WEB_CATALOG_CACHE, ['webCatalogId' => 42]);
        $event = $this->createMock(LifecycleEventArgs::class);

        $this->contentNodeListener->postRemove($contentNode, $event);
    }

    public function testPostRemoveParentRemoved()
    {
        /** @var ContentNode $parentNode */
        $parentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 2, 'parentNode' => $parentNode]);

        $this->messageFactory->expects($this->never())
            ->method('createMessage');
        $this->messageProducer->expects($this->never())
            ->method('send');

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())
            ->method('isScheduledForDelete')
            ->with($parentNode)
            ->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->contentNodeListener->postRemove($contentNode, $event);
    }
}
