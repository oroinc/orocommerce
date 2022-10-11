<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Async\SyncSlugRedirectsProcessor;
use Oro\Bundle\RedirectBundle\Async\Topic\SyncSlugRedirectsTopic;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class SyncSlugRedirectsProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;
    use EntityTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private SyncSlugRedirectsProcessor $processor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->processor = new SyncSlugRedirectsProcessor($this->registry);
        $this->setUpLoggerMock($this->processor);
    }

    public function testProcessRejectWhenSlugNotFound(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(['slugId' => 1]);

        $session = $this->createMock(SessionInterface::class);

        $this->assertSlugRepositoryCalls(1, null);

        $redirectManager = $this->createMock(EntityManagerInterface::class);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Redirect::class)
            ->willReturn($redirectManager);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessSlugNotFound(): void
    {
        $slugId = 42;

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(['slugId' => $slugId]);

        $session = $this->createMock(SessionInterface::class);

        $this->assertSlugRepositoryCalls($slugId, null);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessNoRedirects(): void
    {
        $slugId = '42';
        /** @var Scope $slugScope */
        $slugScope = $this->getEntity(Scope::class, ['id' => 456]);

        /** @var Slug $slug */
        $slug = $this->getEntity(Slug::class, ['id' => $slugId]);
        $slug->addScope($slugScope);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(['slugId' => $slugId]);

        $session = $this->createMock(SessionInterface::class);

        $this->assertSlugRepositoryCalls($slugId, $slug);
        $redirectManager = $this->assertRedirectRepositoryCalls($slug, []);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Redirect::class)
            ->willReturn($redirectManager);

        $redirectManager->expects(self::never())
            ->method('flush');

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessDeadlockException(): void
    {
        $slugId = 42;
        /** @var Scope $slugScope */
        $slugScope = $this->getEntity(Scope::class, ['id' => 456]);

        /** @var Slug $slug */
        $slug = $this->getEntity(Slug::class, ['id' => $slugId]);
        $slug->addScope($slugScope);

        /** @var Redirect $redirect */
        $redirect = $this->getEntity(Redirect::class, ['id' => 123]);
        $exception = $this->createMock(DeadlockException::class);
        $message = $this->createMock(MessageInterface::class);

        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(['slugId' => $slugId]);

        $session = $this->createMock(SessionInterface::class);

        $this->assertSlugRepositoryCalls($slugId, $slug);
        $redirectManager = $this->assertRedirectRepositoryCalls($slug, [$redirect]);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Redirect::class)
            ->willReturn($redirectManager);

        $redirectManager->expects(self::once())
            ->method('flush')
            ->willThrowException($exception);

        self::assertEquals(
            MessageProcessorInterface::REQUEUE,
            $this->processor->process($message, $session)
        );
    }

    public function testProcess(): void
    {
        $slugId = 42;

        /** @var Scope $slugScope */
        $slugScope = $this->getEntity(Scope::class, ['id' => 456]);

        /** @var Slug $slug */
        $slug = $this->getEntity(Slug::class, ['id' => $slugId]);
        $slug->addScope($slugScope);

        /** @var Redirect $redirect */
        $redirect = $this->getEntity(Redirect::class, ['id' => 123]);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(['slugId' => $slugId]);

        $session = $this->createMock(SessionInterface::class);

        $this->assertSlugRepositoryCalls($slugId, $slug);
        $redirectManager = $this->assertRedirectRepositoryCalls($slug, [$redirect]);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Redirect::class)
            ->willReturn($redirectManager);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $session)
        );

        self::assertEquals($slug->getScopes(), $redirect->getScopes());
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals([SyncSlugRedirectsTopic::getName()], SyncSlugRedirectsProcessor::getSubscribedTopics());
    }

    /**
     * @param int $slugId
     * @param Slug|null $slug
     */
    private function assertSlugRepositoryCalls(
        int $slugId,
        ?Slug $slug
    ): void {
        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects(self::once())
            ->method('find')
            ->with($slugId)
            ->willReturn($slug);

        $this->registry
            ->expects(self::once())
            ->method('getRepository')
            ->willReturn($slugRepository);
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
        $redirectRepository->expects(self::once())
            ->method('findBy')
            ->with(['slug' => $slug])
            ->willReturn($redirects);
        $redirectManager = $this->createMock(EntityManagerInterface::class);
        $redirectManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($redirectRepository);

        return $redirectManager;
    }
}
