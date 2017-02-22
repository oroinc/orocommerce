<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Oro\Bundle\RedirectBundle\Async\DelayedJobRunnerDecoratingProcessor;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DelayedJobRunnerDecoratingProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processor;

    /**
     * @var DelayedJobRunnerDecoratingProcessor
     */
    private $decoratorProcessor;

    protected function setUp()
    {
        $this->jobRunner = new JobRunner();
        $this->processor = $this->createMock(MessageProcessorInterface::class);

        $this->decoratorProcessor = new DelayedJobRunnerDecoratingProcessor($this->jobRunner, $this->processor);
    }

    public function testProcess()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode(['id' => 1, 'jobId' => 123]));

        $this->processor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf(MessageInterface::class))
            ->willReturn(MessageProcessorInterface::ACK);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->decoratorProcessor->process($message, $session));
    }

    public function testProcessWithoutJobId()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode(['id' => 1]));

        $this->processor->expects($this->never())
            ->method('process');

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->decoratorProcessor->process($message, $session));
    }
}
