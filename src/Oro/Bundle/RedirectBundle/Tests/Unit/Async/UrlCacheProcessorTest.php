<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Async\UrlCacheProcessor;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;

class UrlCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var JobRunner|MockObject
     */
    private $jobRunner;

    /**
     * @var MessageFactoryInterface|MockObject
     */
    private $messageFactory;

    /**
     * @var SluggableUrlDumper|MockObject
     */
    private $dumper;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var UrlCacheProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);
        $this->dumper = $this->createMock(SluggableUrlDumper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new UrlCacheProcessor(
            $this->dumper,
            $this->logger
        );
        $this->processor->setMessageFactory($this->messageFactory);
        $this->processor->setJobRunner($this->jobRunner);
    }

    public function testProcessInvalidMessage()
    {
        /** @var SessionInterface|MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn(JSON::encode([]));

        $e = new InvalidArgumentException('Message invalid');
        $this->messageFactory->expects($this->once())
            ->method('getEntitiesFromMessage')
            ->willThrowException($e);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Queue Message is invalid', ['exception' => $e, 'message' => $message]);

        $this->assertEquals(UrlCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessInvalidMessageOnGetEntity()
    {
        /** @var SessionInterface|MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn(JSON::encode([]));

        $e = new \Exception('UnexpectedError');
        $this->messageFactory->expects($this->once())
            ->method('getEntitiesFromMessage')
            ->willThrowException($e);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing',
                ['exception' => $e, 'topic' => Topics::PROCESS_CALCULATE_URL_CACHE]
            );

        $this->assertEquals(UrlCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess()
    {
        /** @var SessionInterface|MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $data = ['class' => Product::class, 'entity_ids' => [1], 'jobId' => '123'];
        /** @var MessageInterface|MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn(JSON::encode($data));

        $entity = $this->getEntity(Product::class, ['id' => 1]);
        $this->messageFactory->expects($this->once())
            ->method('getEntitiesFromMessage')
            ->willReturn([$entity]);

        $this->dumper->expects($this->once())
            ->method('dumpByEntity')
            ->with($entity);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturnCallback(
                function (string $delayedJobId, callable $callback) {
                    $this->assertEquals('123', $delayedJobId);

                    $callback();

                    return true;
                }
            );

        $this->assertEquals(UrlCacheProcessor::ACK, $this->processor->process($message, $session));
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::PROCESS_CALCULATE_URL_CACHE],
            UrlCacheProcessor::getSubscribedTopics()
        );
    }
}
