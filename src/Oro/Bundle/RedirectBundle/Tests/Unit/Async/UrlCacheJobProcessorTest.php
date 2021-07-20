<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Oro\Bundle\RedirectBundle\Async\SluggableEntitiesProcessor;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Async\UrlCacheProcessor;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class UrlCacheJobProcessorTest extends \PHPUnit\Framework\TestCase
{
    const JOB_ID = 12358;

    /**
     * @var SluggableUrlDumper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dumper;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var SluggableEntitiesProcessor
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
     * @param array $data
     * @return MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMessage(array $data)
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $messageBody = json_encode($data);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        return $message;
    }

    /**
     * @return SessionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSession()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @dataProvider invalidMessageDataProvider
     */
    public function testProcessInvalidMessage(array $data)
    {
        $message = $this->createMessage($data);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Queue Message is invalid');

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process(
            $message,
            $this->createSession()
        ));
    }

    /**
     * @return array
     */
    public function invalidMessageDataProvider()
    {
        return [
            'no ids' => [
                ['route_name' => 'test']
            ],
            'no route' => [
                ['entity_ids' => [1]]
            ],
            'invalid route name type' => [
                ['route_name' => true, 'entity_ids' => [1]]
            ],
            'invalid entity_ids type' => [
                ['route_name' => 'test', 'entity_ids' => false]
            ],
        ];
    }

    public function testProcessWhenSomeExceptionWasThrownByDumper()
    {
        $data = ['route_name' => 'test', 'entity_ids' => [1]];
        $message = $this->createMessage($data);

        $exception = new \Exception();
        $this->dumper
            ->expects($this->once())
            ->method('dump')
            ->with('test', [1])
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during queue message processing', [
                'topic' => Topics::PROCESS_CALCULATE_URL_CACHE,
                'exception' => $exception
            ]);

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process(
            $message,
            $this->createSession()
        ));
    }

    public function testProcess()
    {
        $data = ['route_name' => 'test', 'entity_ids' => [1]];
        $message = $this->createMessage($data);

        $this->dumper
            ->expects($this->once())
            ->method('dump')
            ->with('test', [1]);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process(
            $message,
            $this->createSession()
        ));
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::PROCESS_CALCULATE_URL_CACHE], UrlCacheProcessor::getSubscribedTopics());
    }
}
