<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\EventListener\ContentNodeListener;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ContentNodeMaterializedPathModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $modifier;

    /**
     * @var ExtraActionEntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var ContentNodeListener
     */
    protected $contentNodeListener;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageProducer;

    /**
     * @var ResolveNodeSlugsMessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageFactory;

    protected function setUp()
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
        /** @var ContentNode $contentNode **/
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 42]);

        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
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

        /** @var AfterFormProcessEvent|\PHPUnit_Framework_MockObject_MockObject $event */
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

        $this->contentNodeListener->postRemove($contentNode);
    }

    public function testPostRemoveNoParent()
    {
        $parentNode = null;

        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 2, 'parentNode' => $parentNode]);

        $this->messageFactory->expects($this->never())
            ->method('createMessage');
        $this->messageProducer->expects($this->never())
            ->method('send');

        $this->contentNodeListener->postRemove($contentNode);
    }
}
