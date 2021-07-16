<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Async\UrlCacheProcessor;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class UrlCacheProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SluggableUrlDumper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dumper;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var UrlCacheProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->dumper = $this->getMockBuilder(SluggableUrlDumper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new UrlCacheProcessor(
            $this->dumper,
            $this->logger
        );
    }

    /**
     * @dataProvider invalidMessageDataProvider
     */
    public function testProcessInvalidMessage(array $data)
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn(JSON::encode($data));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Queue Message is invalid');

        $this->assertEquals(UrlCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    /**
     * @return array
     */
    public function invalidMessageDataProvider()
    {
        return [
            'no route name' => [['entity_ids' => [1]]],
            'no entity ids' => [['route_name' => 'test']],
            'route name is not a string' => [['route_name' => [1], 'entity_ids' => [':|||:']]],
            'entity_ids is not an array' => [['route_name' => 'test', 'entity_ids' => 1]]
        ];
    }

    public function testProcessInvalidMessageOnGetEntity()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn(JSON::encode(['route_name' => 'test', 'entity_ids' => [1]]));

        $this->dumper->expects($this->once())
            ->method('dump')
            ->willThrowException(new \Exception('test'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during queue message processing');

        $this->assertEquals(UrlCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $data = ['route_name' => 'test', 'entity_ids' => [1]];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn(JSON::encode($data));

        $this->dumper->expects($this->once())
            ->method('dump')
            ->with('test', [1]);

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
