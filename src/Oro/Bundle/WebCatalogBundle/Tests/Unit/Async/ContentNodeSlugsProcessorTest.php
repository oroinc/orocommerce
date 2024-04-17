<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebCatalogBundle\Async\ContentNodeSlugsProcessor;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
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
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class ContentNodeSlugsProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use LoggerAwareTraitTestTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private SlugRepository|\PHPUnit\Framework\MockObject\MockObject $slugRepository;

    private DefaultVariantScopesResolver|\PHPUnit\Framework\MockObject\MockObject $defaultVariantScopesResolver;

    private SlugGenerator|\PHPUnit\Framework\MockObject\MockObject $slugGenerator;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private ResolveNodeSlugsMessageFactory|\PHPUnit\Framework\MockObject\MockObject $messageFactory;

    private ContentNodeTreeCache|\PHPUnit\Framework\MockObject\MockObject $contentNodeTreeCache;

    private ContentNodeSlugsProcessor $processor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->defaultVariantScopesResolver = $this->createMock(DefaultVariantScopesResolver::class);
        $this->slugGenerator = $this->createMock(SlugGenerator::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->slugRepository = $this->createMock(SlugRepository::class);
        $this->messageFactory = $this->createMock(ResolveNodeSlugsMessageFactory::class);
        $this->contentNodeTreeCache = $this->createMock(ContentNodeTreeCache::class);
        $this->processor = new ContentNodeSlugsProcessor(
            $this->registry,
            $this->defaultVariantScopesResolver,
            $this->slugGenerator,
            $this->messageProducer,
            $this->messageFactory,
            $this->createMock(LoggerInterface::class),
            $this->contentNodeTreeCache
        );

        $this->setUpLoggerMock($this->processor);
    }

    public function testProcess(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($this->slugRepository);

        $em->expects(self::once())
            ->method('beginTransaction');

        $em->expects(self::never())
            ->method('rollback');

        $em->expects(self::once())
            ->method('flush');

        $em->expects(self::once())
            ->method('commit');

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $contentNodeId = 42;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 2]);
        $contentNode = $this->getEntity(ContentNode::class, ['id' => $contentNodeId, 'webCatalog' => $webCatalog]);
        $body = [
            WebCatalogResolveContentNodeSlugsTopic::ID => $contentNodeId,
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

        $this->contentNodeTreeCache->expects(self::once())
            ->method('deleteForNode')
            ->with($contentNode);

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

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

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

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

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
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($this->slugRepository);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('rollback');
        $em->expects(self::never())
            ->method('commit');
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

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
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($this->slugRepository);

        $em->expects(self::once())
            ->method('beginTransaction');

        $em->expects(self::once())
            ->method('rollback');

        $em->expects(self::never())
            ->method('commit');

        $this->loggerMock->expects(self::once())
            ->method('error');

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);
    }
}
