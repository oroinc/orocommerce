<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebCatalogBundle\Async\ContentNodeSlugsProcessor;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\Model\ResolveNodeSlugsMessageFactory;
use Oro\Bundle\WebCatalogBundle\Resolver\DefaultVariantScopesResolver;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeSlugsProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use LoggerAwareTraitTestTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private DefaultVariantScopesResolver|\PHPUnit\Framework\MockObject\MockObject $defaultVariantScopesResolver;

    private SlugGenerator|\PHPUnit\Framework\MockObject\MockObject $slugGenerator;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private ResolveNodeSlugsMessageFactory|\PHPUnit\Framework\MockObject\MockObject $messageFactory;

    private ContentNodeTreeCache|\PHPUnit\Framework\MockObject\MockObject $contentNodeTreeCache;

    private ContentNodeSlugsProcessor $processor;

    private EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    private WebCatalogRepository|\PHPUnit\Framework\MockObject\MockObject $webCatalogRepo;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->defaultVariantScopesResolver = $this->createMock(DefaultVariantScopesResolver::class);
        $this->slugGenerator = $this->createMock(SlugGenerator::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->createMock(ResolveNodeSlugsMessageFactory::class);
        $this->contentNodeTreeCache = $this->createMock(ContentNodeTreeCache::class);
        $this->processor = new ContentNodeSlugsProcessor(
            $this->registry,
            $this->defaultVariantScopesResolver,
            $this->slugGenerator,
            $this->messageProducer,
            $this->messageFactory,
            $this->contentNodeTreeCache
        );

        $this->setUpLoggerMock($this->processor);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($this->entityManager);

        $this->webCatalogRepo = $this->createMock(WebCatalogRepository::class);
        $this->registry
            ->expects(self::any())
            ->method('getRepository')
            ->with(WebCatalog::class)
            ->willReturn($this->webCatalogRepo);
    }

    public function testProcess(): void
    {
        $this->entityManager->expects(self::once())
            ->method('beginTransaction');

        $this->entityManager->expects(self::never())
            ->method('rollback');

        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->entityManager->expects(self::once())
            ->method('commit');

        $contentNodeId = 42;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 2]);
        $contentNode = $this->getEntity(ContentNode::class, ['id' => $contentNodeId, 'webCatalog' => $webCatalog]);
        $body = [
            WebCatalogResolveContentNodeSlugsTopic::ID => $contentNodeId,
            WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
        ];

        $message = new Message();
        $message->setBody($body);

        $session = $this->createMock(SessionInterface::class);

        $this->messageFactory->expects(self::once())
            ->method('getEntityFromMessage')
            ->with($body)
            ->willReturn($contentNode);
        $this->messageFactory->expects(self::once())
            ->method('getCreateRedirectFromMessage')
            ->with($body)
            ->willReturn(true);

        $this->defaultVariantScopesResolver->expects(self::once())
            ->method('resolve')
            ->with($contentNode);

        $this->slugGenerator->expects(self::once())
            ->method('generate')
            ->with($contentNode, true);

        $scopeIds = [42, 142];
        $this->webCatalogRepo
            ->expects(self::once())
            ->method('getUsedScopesIds')
            ->with($webCatalog)
            ->willReturn($scopeIds);
        $this->contentNodeTreeCache->expects(self::once())
            ->method('deleteMultiple')
            ->with([[$contentNodeId, [$scopeIds[0]]], [$contentNodeId, [$scopeIds[1]]]]);

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWithException(): void
    {
        $contentNodeId = 42;
        $contentNode = $this->getEntity(ContentNode::class, ['id' => $contentNodeId, 'webCatalog' => new WebCatalog()]);

        $body = [
            WebCatalogResolveContentNodeSlugsTopic::ID => $contentNodeId,
            WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
        ];

        $message = new Message();
        $message->setBody($body);

        $session = $this->createMock(SessionInterface::class);

        $this->messageFactory->expects(self::once())
            ->method('getEntityFromMessage')
            ->with($body)
            ->willReturn($contentNode);
        $this->messageFactory->expects(self::once())
            ->method('getCreateRedirectFromMessage')
            ->with($body)
            ->willReturn(true);

        $this->defaultVariantScopesResolver->expects(self::once())
            ->method('resolve')
            ->willThrowException(new \Exception());
        $this->messageProducer->expects(self::never())
            ->method('send');
        $this->assertRollback();

        self::assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessWithUniqueConstraintException(): void
    {
        $contentNodeId = 42;
        $contentNode = $this->getEntity(ContentNode::class, ['id' => $contentNodeId, 'webCatalog' => new WebCatalog()]);

        $body = [
            WebCatalogResolveContentNodeSlugsTopic::ID => $contentNodeId,
            WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
        ];

        $message = new Message();
        $message->setBody($body);

        $session = $this->createMock(SessionInterface::class);

        $this->messageFactory->expects(self::once())
            ->method('getEntityFromMessage')
            ->with($body)
            ->willReturn($contentNode);
        $this->messageFactory->expects(self::once())
            ->method('getCreateRedirectFromMessage')
            ->with($body)
            ->willReturn(true);

        $this->defaultVariantScopesResolver->expects(self::once())
            ->method('resolve')
            ->willThrowException($this->createMock(UniqueConstraintViolationException::class));

        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects(self::once())
            ->method('rollback');
        $this->entityManager->expects(self::never())
            ->method('commit');
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($this->entityManager);

        $this->messageProducer->expects(self::never())
            ->method('send');

        self::assertEquals(MessageProcessorInterface::REQUEUE, $this->processor->process($message, $session));
    }

    public function testProcessContentNodeNotFound(): void
    {
        $body = [
            WebCatalogResolveContentNodeSlugsTopic::ID => 42,
            WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
        ];

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        $session = $this->createMock(SessionInterface::class);

        $this->messageFactory->expects(self::once())
            ->method('getEntityFromMessage')
            ->with($body)
            ->willReturn(null);

        $this->defaultVariantScopesResolver->expects(self::never())
            ->method('resolve');
        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Content node #{id} is not found', $body);

        self::assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [WebCatalogResolveContentNodeSlugsTopic::getName()],
            ContentNodeSlugsProcessor::getSubscribedTopics()
        );
    }

    protected function assertRollback(): void
    {
        $this->entityManager->expects(self::once())
            ->method('beginTransaction');

        $this->entityManager->expects(self::once())
            ->method('rollback');

        $this->entityManager->expects(self::never())
            ->method('commit');

        $this->loggerMock->expects(self::once())
            ->method('error');
    }
}
