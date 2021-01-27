<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Async\SyncSlugRedirectsProcessor;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class SyncSlugRedirectsProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var SyncSlugRedirectsProcessor
     */
    protected $processor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = new SyncSlugRedirectsProcessor(
            $this->registry,
            $this->logger
        );
    }

    public function testProcessRejectWhenSlugNotFound()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => 1]));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $slugManager = $this->createMock(EntityManagerInterface::class);
        $slugManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($slugRepository);

        $redirectManager = $this->createMock(EntityManagerInterface::class);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Slug::class, $slugManager],
                [Redirect::class, $redirectManager]
            ]);
        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessRejectInvalidMessage()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([]));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Queue Message is invalid');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessSlugNotFound()
    {
        $slugId = 42;

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => $slugId]));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $slugManager = $this->assertSlugRepositoryCalls($slugId, null);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($slugManager);

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessNoRedirects()
    {
        $slugId = '42';
        /** @var Scope $slugScope */
        $slugScope = $this->getEntity(Scope::class, ['id' => 456]);

        /** @var Slug $slug */
        $slug = $this->getEntity(Slug::class, ['id' => $slugId]);
        $slug->addScope($slugScope);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => $slugId]));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $slugManager = $this->assertSlugRepositoryCalls($slugId, $slug);
        $redirectManager = $this->assertRedirectRepositoryCalls($slug, []);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Slug::class, $slugManager],
                [Redirect::class, $redirectManager]
            ]);

        $redirectManager->expects($this->never())
            ->method('flush');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessDeadlockException()
    {
        $slugId = 42;
        /** @var Scope $slugScope */
        $slugScope = $this->getEntity(Scope::class, ['id' => 456]);

        /** @var Slug $slug */
        $slug = $this->getEntity(Slug::class, ['id' => $slugId]);
        $slug->addScope($slugScope);

        /** @var Redirect $redirect */
        $redirect = $this->getEntity(Redirect::class, ['id' => 123]);

        /** @var DeadlockException|\PHPUnit\Framework\MockObject\MockObject $exception */
        $exception = $this->createMock(DeadlockException::class);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => $slugId]));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $slugManager = $this->assertSlugRepositoryCalls($slugId, $slug);
        $redirectManager = $this->assertRedirectRepositoryCalls($slug, [$redirect]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Slug::class, $slugManager],
                [Redirect::class, $redirectManager]
            ]);

        $redirectManager->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $this->assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($message, $session)
        );
    }

    public function testProcess()
    {
        $slugId = 42;

        /** @var Scope $slugScope */
        $slugScope = $this->getEntity(Scope::class, ['id' => 456]);

        /** @var Slug $slug */
        $slug = $this->getEntity(Slug::class, ['id' => $slugId]);
        $slug->addScope($slugScope);

        /** @var Redirect $redirect */
        $redirect = $this->getEntity(Redirect::class, ['id' => 123]);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => $slugId]));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $slugManager = $this->assertSlugRepositoryCalls($slugId, $slug);
        $redirectManager = $this->assertRedirectRepositoryCalls($slug, [$redirect]);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Slug::class, $slugManager],
                [Redirect::class, $redirectManager]
            ]);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $session)
        );

        $this->assertEquals($slug->getScopes(), $redirect->getScopes());
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::SYNC_SLUG_REDIRECTS], $this->processor->getSubscribedTopics());
    }

    /**
     * @param int $slugId
     * @param Slug|null $slug
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertSlugRepositoryCalls(
        int $slugId,
        ?Slug $slug
    ) {
        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects($this->once())
            ->method('find')
            ->with($slugId)
            ->willReturn($slug);

        $slugManager = $this->createMock(EntityManagerInterface::class);
        $slugManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($slugRepository);

        return $slugManager;
    }

    /**
     * @param Slug $slug
     * @param Redirect[] $redirects
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertRedirectRepositoryCalls(
        Slug $slug,
        array $redirects
    ) {
        $redirectRepository = $this->createMock(RedirectRepository::class);
        $redirectRepository->expects($this->once())
            ->method('findBy')
            ->with(['slug' => $slug])
            ->willReturn($redirects);
        $redirectManager = $this->createMock(EntityManagerInterface::class);
        $redirectManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($redirectRepository);

        return $redirectManager;
    }
}
