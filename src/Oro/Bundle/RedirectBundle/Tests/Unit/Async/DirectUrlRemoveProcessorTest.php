<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Async\DirectUrlRemoveProcessor;
use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheMassTopic;
use Oro\Bundle\RedirectBundle\Async\Topic\RemoveDirectUrlForEntityTypeTopic;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DirectUrlRemoveProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $producer;

    private DirectUrlRemoveProcessor $processor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $this->processor = new DirectUrlRemoveProcessor($this->registry, $this->producer);
        $this->setUpLoggerMock($this->processor);
    }

    public function testProcessExceptionInTransaction(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $messageBody = \stdClass::class;
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($messageBody);

        $session = $this->createMock(SessionInterface::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('rollback');

        $exception = new \Exception('Test');
        $repository = $this->createMock(SlugRepository::class);
        $repository->expects(self::once())
            ->method('deleteSlugAttachedToEntityByClass')
            ->with($messageBody)
            ->willThrowException($exception);

        $em->expects(self::once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($repository);
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with($messageBody)
            ->willReturn($em);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Direct URL removal',
                ['exception' => $exception]
            );

        $this->producer->expects(self::never())->method('send');

        self::assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $messageBody = \stdClass::class;
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($messageBody);

        $session = $this->createMock(SessionInterface::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');

        $repository = $this->createMock(SlugRepository::class);
        $repository->expects(self::once())
            ->method('deleteSlugAttachedToEntityByClass')
            ->with($messageBody);

        $em->expects(self::once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($repository);
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with($messageBody)
            ->willReturn($em);

        $this->producer
            ->expects(self::once())
            ->method('send')
            ->with(
                CalculateSlugCacheMassTopic::getName(),
                [DirectUrlMessageFactory::ENTITY_CLASS_NAME => $messageBody, DirectUrlMessageFactory::ID => []]
            );

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [RemoveDirectUrlForEntityTypeTopic::getName()],
            DirectUrlRemoveProcessor::getSubscribedTopics()
        );
    }
}
