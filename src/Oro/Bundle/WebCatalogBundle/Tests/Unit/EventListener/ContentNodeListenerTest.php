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

    /**
     * @var ContentNodeMaterializedPathModifier|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $modifier;

    /**
     * @var ExtraActionEntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storage;

    /**
     * @var ContentNodeListener
     */
    protected $contentNodeListener;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageProducer;

    /**
     * @var ResolveNodeSlugsMessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageFactory;

    protected function setUp(): void
    {
        $this->modifier = $this->getMockBuilder(ContentNodeMaterializedPathModifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = $this->createMock(ExtraActionEntityStorageInterface::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->getMockBuilder(ResolveNodeSlugsMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->modifier->expects($this->once())
            ->method('calculateMaterializedPath')
            ->with($contentNode);

        $this->contentNodeListener->postPersist($contentNode);
    }

    public function testPreUpdate()
    {
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 42]);

        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $args */
        $args = $this->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $args->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn(
                [
                    ContentNode::FIELD_PARENT_NODE => [
                        null,
                        new ContentNode()
                    ]
                ]
            );

        $childNode = new ContentNode();

        $this->modifier->expects($this->once())
            ->method('calculateChildrenMaterializedPath')
            ->with($contentNode)
            ->willReturn([new ContentNode()]);

        $this->storage->expects($this->at(0))
            ->method('scheduleForExtraInsert')
            ->with($contentNode);

        $this->storage->expects($this->at(1))
            ->method('scheduleForExtraInsert')
            ->with($childNode);

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

        /** @var AfterFormProcessEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(AfterFormProcessEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
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

        /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('isScheduledForDelete')
            ->with($parentNode)
            ->willReturn(false);
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        /** @var LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        /** @var LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject $uow */
        $uow = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('isScheduledForDelete')
            ->with($parentNode)
            ->willReturn(true);
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        /** @var LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->contentNodeListener->postRemove($contentNode, $event);
    }
}
