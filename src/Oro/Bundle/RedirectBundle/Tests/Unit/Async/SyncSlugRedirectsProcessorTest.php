<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\DBAL\Driver\AbstractDriverException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Bundle\RedirectBundle\Async\SyncSlugRedirectsProcessor;

class SyncSlugRedirectsProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var DatabaseExceptionHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $databaseExceptionHelper;

    /**
     * @var SyncSlugRedirectsProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->databaseExceptionHelper = $this->getMockBuilder(DatabaseExceptionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = new SyncSlugRedirectsProcessor(
            $this->registry,
            $this->logger,
            $this->databaseExceptionHelper
        );
    }

    public function testProcessRejectInvalidMessage()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([]));

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Message is invalid. Key "slugId" is missing from message data.', ['message' => $message]);

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessExceptionInTransaction()
    {
        $slugId = 42;

        /** @var AbstractDriverException|\PHPUnit_Framework_MockObject_MockObject $exception */
        $exception = new \Exception('Some exception');

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => $slugId]));

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
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

        $this->databaseExceptionHelper->expects($this->never())
            ->method('isDeadlock');

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

        /** @var AbstractDriverException|\PHPUnit_Framework_MockObject_MockObject $exception */
        $exception = $this->getMockBuilder(AbstractDriverException::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => $slugId]));

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
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

        $this->databaseExceptionHelper->expects($this->once())
            ->method('isDeadlock')
            ->willReturn(true);

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

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['slugId' => $slugId]));

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
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
        $this->assertEquals(
            [Topics::GENERATE_SLUG_REDIRECTS],
            $this->processor->getSubscribedTopics()
        );
    }
}
