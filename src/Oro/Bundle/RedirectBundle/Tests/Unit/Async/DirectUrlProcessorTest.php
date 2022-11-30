<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Async\DirectUrlProcessor;
use Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\UrlCacheAllCapabilities;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class DirectUrlProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private SlugEntityGenerator|\PHPUnit\Framework\MockObject\MockObject $generator;

    private MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $messageFactory;

    private DirectUrlProcessor $processor;

    private UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject $urlCache;

    private SluggableUrlDumper|\PHPUnit\Framework\MockObject\MockObject $urlCacheDumper;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->generator = $this->createMock(SlugEntityGenerator::class);
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        $this->urlCache = $this->createMock(UrlCacheInterface::class);
        $this->urlCacheDumper = $this->createMock(SluggableUrlDumper::class);

        $this->processor = new DirectUrlProcessor(
            $this->registry,
            $this->generator,
            $this->messageFactory,
            $this->createMock(LoggerInterface::class),
            $this->urlCache
        );
        $this->processor->setUrlCacheDumper($this->urlCacheDumper);
        $this->setUpLoggerMock($this->processor);
    }

    public function testProcessExceptionInTransaction(): void
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $exception = new \Exception('Test');
        $message = $this->prepareMessage();
        $this->generator->expects(self::once())
            ->method('generateWithoutCacheDump')
            ->willThrowException($exception);

        $this->assertTransactionRollback();

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL generation',
                ['exception' => $exception]
            );

        self::assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessUniqueConstraintException(): void
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $exception = $this->createMock(UniqueConstraintViolationException::class);
        $message = $this->prepareMessage();
        $this->generator->expects(self::once())
            ->method('generateWithoutCacheDump')
            ->willThrowException($exception);

        $this->assertTransactionRollback();

        self::assertEquals(MessageProcessorInterface::REQUEUE, $this->processor->process($message, $session));
    }

    public function testProcessExceptionInClosedTransaction(): void
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $exception = new \Exception('Test');
        $message = $this->prepareMessage();
        $this->generator->expects(self::once())
            ->method('generateWithoutCacheDump')
            ->willThrowException($exception);

        $em = $this->assertTransactionStarted();

        $conn = $this->createMock(Connection::class);
        $conn->expects(self::once())
            ->method('getTransactionNestingLevel')
            ->willReturn(0);
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($conn);

        $em->expects(self::never())
            ->method('rollback');

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL generation',
                ['exception' => $exception]
            );

        self::assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessExceptionDeadlockInTransaction(): void
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        /** @var DeadlockException|\PHPUnit\Framework\MockObject\MockObject $exception */
        $exception = $this->createMock(DeadlockException::class);

        $message = $this->prepareMessage();
        $this->generator->expects(self::once())
            ->method('generateWithoutCacheDump')
            ->willThrowException($exception);
        $this->assertTransactionRollback();

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL generation',
                ['exception' => $exception]
            );

        self::assertEquals(MessageProcessorInterface::REQUEUE, $this->processor->process($message, $session));
    }

    /**
     * @dataProvider processProvider
     * @param bool $createRedirect
     */
    public function testProcess($createRedirect): void
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $this->urlCache->expects(self::once())
            ->method('removeUrl')
            ->with(UrlCacheInterface::SLUG_ROUTES_KEY, []);
        $this->assertProcessorSuccessfulCalled($message, $createRedirect);

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWithFlushableCache(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMock(MessageInterface::class);

        $this->assertProcessorSuccessfulCalled($message, false);

        $urlCache = $this->createMock(UrlCacheAllCapabilities::class);
        $urlCache->expects(self::once())
            ->method('removeUrl')
            ->with(UrlCacheInterface::SLUG_ROUTES_KEY, []);
        $urlCache->expects(self::once())
            ->method('flushAll');
        $processor = new DirectUrlProcessor(
            $this->registry,
            $this->generator,
            $this->messageFactory,
            $this->createMock(LoggerInterface::class),
            $urlCache
        );
        $processor->setUrlCacheDumper($this->urlCacheDumper);
        $processor->setLogger($this->loggerMock);

        $this->urlCacheDumper
            ->expects(self::once())
            ->method('dump');

        self::assertEquals(MessageProcessorInterface::ACK, $processor->process($message, $session));
    }

    /**
     * @return array
     */
    public function processProvider(): array
    {
        return [
            'create redirect true' => [
                'createRedirect' => true,
            ],
            'create redirect false' => [
                'createRedirect' => false,
            ],
        ];
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals([GenerateDirectUrlForEntitiesTopic::getName()], DirectUrlProcessor::getSubscribedTopics());
    }

    /**
     * @return MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function prepareMessage()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $class = \stdClass::class;
        $messageData = ['class' => $class, 'id' => null];
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($messageData);

        $this->messageFactory->expects(self::once())
            ->method('getEntityClassFromMessage')
            ->with($messageData)
            ->willReturn($class);

        $this->messageFactory->expects(self::once())
            ->method('getCreateRedirectFromMessage')
            ->willReturn(true);

        $entity = $this->createMock(SluggableInterface::class);
        $this->messageFactory->expects(self::once())
            ->method('getEntitiesFromMessage')
            ->with($messageData)
            ->willReturn([$entity]);

        return $message;
    }

    private function assertTransactionCommitted(): void
    {
        $em = $this->assertTransactionStarted();
        $em->expects(self::once())
            ->method('commit');
    }

    private function assertTransactionRollback(): void
    {
        $em = $this->assertTransactionStarted();

        $conn = $this->createMock(Connection::class);
        $conn->method('getTransactionNestingLevel')
            ->willReturn(1);
        $em->method('getConnection')
            ->willReturn($conn);

        $em->expects(self::once())
            ->method('rollback');
    }

    /**
     * @param bool $createRedirect
     * @param string $messageData
     * @param string $class
     * @param object $entity
     */
    private function assertMessageFactoryCallsDuringProcess(
        $createRedirect,
        $messageData,
        $class,
        $entity
    ): void {
        $this->messageFactory->expects(self::once())
            ->method('getEntityClassFromMessage')
            ->with($messageData)
            ->willReturn($class);

        $this->messageFactory->expects(self::once())
            ->method('getEntitiesFromMessage')
            ->with($messageData)
            ->willReturn([$entity]);

        $this->messageFactory->expects(self::once())
            ->method('getCreateRedirectFromMessage')
            ->with($messageData)
            ->willReturn($createRedirect);
    }

    /**
     * @param MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message
     * @param bool $createRedirect
     */
    private function assertProcessorSuccessfulCalled($message, $createRedirect): void
    {
        $class = \stdClass::class;
        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $messageBody = ['class' => $class, 'id' => null];
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($messageBody);

        $this->assertTransactionCommitted();
        $this->assertMessageFactoryCallsDuringProcess($createRedirect, $messageBody, $class, $entity);

        $this->generator->expects(self::any())
            ->method('generate')
            ->with($entity, $createRedirect);

        $this->generator->expects(self::any())
            ->method('generateWithoutCacheDump')
            ->with($entity, $createRedirect);
    }

    /**
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertTransactionStarted()
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $em;
    }
}
