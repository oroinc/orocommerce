<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Async\DirectUrlRemoveProcessor;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class DirectUrlRemoveProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $producer;

    /**
     * @var DirectUrlRemoveProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $this->processor = new DirectUrlRemoveProcessor($this->registry, $this->logger, $this->producer);
    }

    public function testProcessExceptionOutsideTransaction()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $messageData = \stdClass::class;
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);
        $exception = new \Exception('Test');
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willThrowException($exception);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($messageData)
            ->willReturn($em);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL removal',
                ['exception' => $exception]
            );

        $this->producer->expects($this->never())->method('send');

        $this->assertEquals(DirectUrlRemoveProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessExceptionInTransaction()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $messageData = \stdClass::class;
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('rollback');

        $exception = new \Exception('Test');
        $repository = $this->getMockBuilder(SlugRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('deleteSlugAttachedToEntityByClass')
            ->with($messageData)
            ->willThrowException($exception);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($messageData)
            ->willReturn($em);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL removal',
                ['exception' => $exception]
            );

        $this->producer->expects($this->never())->method('send');

        $this->assertEquals(DirectUrlRemoveProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessNoEntityManagerFound()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $messageData = \stdClass::class;
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($messageData)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(sprintf('Entity manager is not defined for class: "%s"', $messageData));

        $this->producer->expects($this->never())->method('send');

        $this->assertEquals(DirectUrlRemoveProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $messageData = \stdClass::class;
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');

        $repository = $this->getMockBuilder(SlugRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('deleteSlugAttachedToEntityByClass')
            ->with($messageData);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($messageData)
            ->willReturn($em);

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::CALCULATE_URL_CACHE_MASS, '');

        $this->assertEquals(DirectUrlRemoveProcessor::ACK, $this->processor->process($message, $session));
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::REMOVE_DIRECT_URL_FOR_ENTITY_TYPE],
            DirectUrlRemoveProcessor::getSubscribedTopics()
        );
    }
}
