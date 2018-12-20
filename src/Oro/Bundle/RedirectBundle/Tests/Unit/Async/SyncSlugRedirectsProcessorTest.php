<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
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

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = new SyncSlugRedirectsProcessor(
            $this->registry,
            $this->logger
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
            ->method('critical')
            ->with('Message is invalid. Key "slugId" is missing from message data.');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessExceptionInTransaction()
    {
        $slugId = 42;

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => $slugId]));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects($this->once())
            ->method('find')
            ->with($slugId)
            ->willThrowException(new \Exception('Some exception'));

        $slugManager = $this->createMock(EntityManagerInterface::class);
        $slugManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($slugRepository);

        $redirectManager = $this->createMock(EntityManagerInterface::class);
        $redirectManager->expects($this->once())
            ->method('beginTransaction');

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Slug::class, $slugManager],
                [Redirect::class, $redirectManager]
            ]);

        $redirectManager->expects($this->never())
            ->method('flush');

        $redirectManager->expects($this->never())
            ->method('commit');

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessExceptionDeadlockInTransaction()
    {
        $slugId = 42;

        /** @var DeadlockException|\PHPUnit\Framework\MockObject\MockObject $exception */
        $exception = $this->createMock(DeadlockException::class);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => $slugId]));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects($this->once())
            ->method('find')
            ->with($slugId)
            ->willThrowException($exception);

        $slugManager = $this->createMock(EntityManagerInterface::class);
        $slugManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($slugRepository);

        $redirectManager = $this->createMock(EntityManagerInterface::class);
        $redirectManager->expects($this->once())
            ->method('beginTransaction');

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Slug::class, $slugManager],
                [Redirect::class, $redirectManager]
            ]);

        $redirectManager->expects($this->never())
            ->method('flush');

        $redirectManager->expects($this->never())
            ->method('commit');

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

        $redirect = $this->getEntity(Redirect::class, ['id' => 123]);

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => $slugId]));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $slugRepository = $this->createMock(SlugRepository::class);
        $slugRepository->expects($this->once())
            ->method('find')
            ->with($slugId)
            ->willReturn($slug);

        $redirectRepository = $this->createMock(RedirectRepository::class);
        $redirectRepository->expects($this->once())
            ->method('findBy')
            ->with(['slug' => $slug])
            ->willReturn([$redirect]);

        $slugManager = $this->createMock(EntityManagerInterface::class);
        $slugManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($slugRepository);

        $redirectManager = $this->createMock(EntityManagerInterface::class);
        $redirectManager->expects($this->once())
            ->method('beginTransaction');
        $redirectManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($redirectRepository);

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
}
