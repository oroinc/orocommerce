<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\AbstractDriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\RedirectBundle\Async\DirectUrlProcessor;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\UrlCacheAllCapabilities;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class DirectUrlProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var SlugEntityGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $generator;

    /**
     * @var MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var DirectUrlProcessor
     */
    private $processor;

    /**
     * @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlCache;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->generator = $this->createMock(SlugEntityGenerator::class);
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->urlCache = $this->createMock(UrlCacheInterface::class);

        $this->processor = new DirectUrlProcessor(
            $this->registry,
            $this->generator,
            $this->messageFactory,
            $this->logger,
            $this->urlCache
        );
    }

    public function testProcessInvalidMessage()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $exception = new InvalidArgumentException('Test');
        $message = $this->prepareMessageTrowingException($exception);
        $this->assertTransactionRollback();

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Queue Message is invalid',
                [
                    'exception' => $exception
                ]
            );

        $this->assertEquals(DirectUrlProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessInvalidMessageOnGetEntity()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $exception = new InvalidArgumentException('Test');
        $message = $this->prepareMessageTrowingException($exception);
        $this->assertTransactionRollback();

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Queue Message is invalid',
                [
                    'exception' => $exception,
                ]
            );

        $this->assertEquals(DirectUrlProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessExceptionOutsideTransaction()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $class = \stdClass::class;
        $id = null;
        $messageData = ['class' => $class, 'id' => $id];
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);
        $exception = new \Exception('Test');
        $this->messageFactory->expects($this->once())
            ->method('getEntityClassFromMessage')
            ->with($messageData)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL generation',
                ['exception' => $exception]
            );

        $this->assertEquals(DirectUrlProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessExceptionInTransaction()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $exception = new \Exception('Test');
        $message = $this->prepareMessageTrowingException($exception);
        $this->assertTransactionRollback();

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL generation',
                ['exception' => $exception]
            );

        $this->assertEquals(DirectUrlProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessExceptionDeadlockInTransaction()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        /** @var DeadlockException|\PHPUnit\Framework\MockObject\MockObject $exception */
        $exception = $this->createMock(DeadlockException::class);
        $message = $this->prepareMessageTrowingException($exception);
        $this->assertTransactionRollback();

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL generation',
                ['exception' => $exception]
            );

        $this->assertEquals(DirectUrlProcessor::REQUEUE, $this->processor->process($message, $session));
    }

    /**
     * @dataProvider processProvider
     * @param bool $createRedirect
     */
    public function testProcess($createRedirect)
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $this->urlCache->expects($this->once())
            ->method('removeUrl')
            ->with(UrlCacheInterface::SLUG_ROUTES_KEY, []);
        $this->assertProcessorSuccessfulCalled($message, $createRedirect);

        $this->assertEquals(DirectUrlProcessor::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWithFlushableCache()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $this->assertProcessorSuccessfulCalled($message, false);

        /** @var UrlCacheInterface|\PHPUnit\Framework\MockObject\MockObject $urlCache */
        $urlCache = $this->createMock(UrlCacheAllCapabilities::class);
        $urlCache->expects($this->once())
            ->method('removeUrl')
            ->with(UrlCacheInterface::SLUG_ROUTES_KEY, []);
        $urlCache->expects($this->once())
            ->method('flushAll');
        $processor = new DirectUrlProcessor(
            $this->registry,
            $this->generator,
            $this->messageFactory,
            $this->logger,
            $urlCache
        );

        $this->assertEquals(DirectUrlProcessor::ACK, $processor->process($message, $session));
    }

    /**
     * @return array
     */
    public function processProvider()
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

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::GENERATE_DIRECT_URL_FOR_ENTITIES], $this->processor->getSubscribedTopics());
    }

    /**
     * @param \Exception $exception
     * @return MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function prepareMessageTrowingException(\Exception $exception)
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $class = \stdClass::class;
        $messageData = ['class' => $class, 'id' => null];
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($messageData));

        $this->messageFactory->expects($this->once())
            ->method('getEntityClassFromMessage')
            ->with($messageData)
            ->willReturn($class);

        $this->messageFactory->expects($this->once())
            ->method('getEntitiesFromMessage')
            ->with($messageData)
            ->willThrowException($exception);

        return $message;
    }

    private function assertTransactionCommitted()
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('commit');
    }

    private function assertTransactionRollback()
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);
        $em->expects($this->once())
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
    ) {
        $this->messageFactory->expects($this->once())
            ->method('getEntityClassFromMessage')
            ->with($messageData)
            ->willReturn($class);

        $this->messageFactory->expects($this->once())
            ->method('getEntitiesFromMessage')
            ->with($messageData)
            ->willReturn([$entity]);

        $this->messageFactory->expects($this->once())
            ->method('getCreateRedirectFromMessage')
            ->with($messageData)
            ->willReturn($createRedirect);
    }

    /**
     * @param MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message
     * @param bool $createRedirect
     */
    private function assertProcessorSuccessfulCalled($message, $createRedirect)
    {
        $class = \stdClass::class;
        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $messageData = ['class' => $class, 'id' => null];
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        $this->assertTransactionCommitted();
        $this->assertMessageFactoryCallsDuringProcess($createRedirect, $messageData, $class, $entity);

        $this->generator->expects($this->once())
            ->method('generate')
            ->with($entity, $createRedirect);
    }
}
