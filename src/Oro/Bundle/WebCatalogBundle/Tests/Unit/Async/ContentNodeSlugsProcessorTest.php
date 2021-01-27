<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Async\ContentNodeSlugsProcessor;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Bundle\WebCatalogBundle\Resolver\DefaultVariantScopesResolver;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class ContentNodeSlugsProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var DefaultVariantScopesResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $defaultVariantScopesResolver;

    /**
     * @var SlugGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $slugGenerator;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageProducer;

    /**
     * @var ResolveNodeSlugsMessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var ContentNodeTreeCache|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contentNodeTreeCache;

    /**
     * @var ContentNodeSlugsProcessor
     */
    protected $processor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->defaultVariantScopesResolver = $this->getMockBuilder(DefaultVariantScopesResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->slugGenerator = $this->getMockBuilder(SlugGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->getMockBuilder(ResolveNodeSlugsMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->contentNodeTreeCache = $this->createMock(ContentNodeTreeCache::class);
        $this->processor = new ContentNodeSlugsProcessor(
            $this->registry,
            $this->defaultVariantScopesResolver,
            $this->slugGenerator,
            $this->messageProducer,
            $this->messageFactory,
            $this->logger,
            $this->contentNodeTreeCache
        );
    }

    public function testProcess()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects($this->never())
            ->method('rollback');

        $em->expects($this->once())
            ->method('flush');

        $em->expects($this->once())
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $contentNodeId = 42;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 2]);
        $contentNode = $this->getEntity(ContentNode::class, ['id' => $contentNodeId, 'webCatalog' => $webCatalog]);
        $body = [
            ResolveNodeSlugsMessageFactory::ID => $contentNodeId,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true
        ];

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($body)
            ->willReturn($contentNode);
        $this->messageFactory->expects($this->once())
            ->method('getCreateRedirectFromMessage')
            ->with($body)
            ->willReturn(true);

        $this->defaultVariantScopesResolver->expects($this->once())
            ->method('resolve')
            ->with($contentNode);

        $this->slugGenerator->expects($this->once())
            ->method('generate')
            ->with($contentNode, true);

        $this->contentNodeTreeCache->expects($this->once())
            ->method('deleteForNode')
            ->with($contentNode);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWithException()
    {
        $contentNodeId = 42;
        $contentNode = $this->getEntity(ContentNode::class, ['id' => $contentNodeId, 'webCatalog' => new WebCatalog()]);

        $body = [
            ResolveNodeSlugsMessageFactory::ID => $contentNodeId,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true
        ];

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($body)
            ->willReturn($contentNode);
        $this->messageFactory->expects($this->once())
            ->method('getCreateRedirectFromMessage')
            ->with($body)
            ->willReturn(true);

        $this->defaultVariantScopesResolver->expects($this->once())
            ->method('resolve')
            ->willThrowException(new \Exception());
        $this->messageProducer->expects($this->never())
            ->method('send');
        $this->assertRollback();

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessWithUniqueConstraintException()
    {
        $contentNodeId = 42;
        $contentNode = $this->getEntity(ContentNode::class, ['id' => $contentNodeId, 'webCatalog' => new WebCatalog()]);

        $body = [
            ResolveNodeSlugsMessageFactory::ID => $contentNodeId,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true
        ];

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($body)
            ->willReturn($contentNode);
        $this->messageFactory->expects($this->once())
            ->method('getCreateRedirectFromMessage')
            ->with($body)
            ->willReturn(true);

        $this->defaultVariantScopesResolver->expects($this->once())
            ->method('resolve')
            ->willThrowException($this->createMock(UniqueConstraintViolationException::class));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('rollback');
        $em->expects($this->never())
            ->method('commit');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $this->messageProducer->expects($this->never())
            ->method('send');

        $this->assertEquals(MessageProcessorInterface::REQUEUE, $this->processor->process($message, $session));
    }

    public function testProcessContentNodeNotFound()
    {
        $body = [
            ResolveNodeSlugsMessageFactory::ID => 42,
            ResolveNodeSlugsMessageFactory::CREATE_REDIRECT => true
        ];

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->messageFactory->expects($this->once())
            ->method('getEntityFromMessage')
            ->with($body)
            ->willReturn(null);

        $this->defaultVariantScopesResolver->expects($this->never())
            ->method('resolve');
        $this->messageProducer->expects($this->never())
            ->method('send');
        $this->assertRollback();

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::RESOLVE_NODE_SLUGS], ContentNodeSlugsProcessor::getSubscribedTopics());
    }

    protected function assertRollback()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('beginTransaction');

        $em->expects($this->once())
            ->method('rollback');

        $em->expects($this->never())
            ->method('commit');

        $this->logger->expects($this->once())
            ->method('error');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);
    }
}
