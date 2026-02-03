<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Handler;

use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Bundle\ProductBundle\Exception\FailedToRunReindexProductCollectionJobException;
use Oro\Bundle\ProductBundle\Handler\AsyncReindexProductCollectionHandler;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AsyncReindexProductCollectionHandlerTest extends TestCase
{
    private JobRunner&MockObject $jobRunner;
    private DependentJobService&MockObject $dependentJobService;
    private MessageProducerInterface&MockObject $messageProducer;
    private SegmentMessageFactory&MockObject $messageFactory;
    private JobProcessor&MockObject $jobProcessor;
    private AsyncReindexProductCollectionHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->dependentJobService = $this->createMock(DependentJobService::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->createMock(SegmentMessageFactory::class);
        $this->jobProcessor = $this->createMock(JobProcessor::class);

        $this->handler = new AsyncReindexProductCollectionHandler(
            $this->jobRunner,
            $this->dependentJobService,
            $this->messageProducer,
            $this->messageFactory,
            $this->jobProcessor
        );
    }

    public function testHandleReturnsFalseWhenActiveReindexJobExists(): void
    {
        $jobName = 'test:job:name';

        $this->assertJobProcessorWithActiveReindexJob($jobName);
        $this->jobRunner->expects(self::never())
            ->method('runUnique');

        $result = $this->handler->handle([], $jobName);

        self::assertFalse($result);
    }

    public function testHandleThrowsExceptionWhenActiveReindexJobExistsAndThrowEnabled(): void
    {
        $jobName = 'test:job:name';

        $this->assertJobProcessorWithActiveReindexJob($jobName);
        $this->jobRunner->expects(self::never())
            ->method('runUnique');

        $this->expectException(FailedToRunReindexProductCollectionJobException::class);

        $this->handler->handle([], $jobName, true);
    }

    public function testHandleWhenNoActiveReindexJob(): void
    {
        $jobName = 'test:job:name';

        $this->assertJobProcessorWithCompletedReindexJob($jobName);
        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->willReturn(true);

        $result = $this->handler->handle([], $jobName);

        self::assertTrue($result);
    }

    public function testHandleWhenNoPreviousJob(): void
    {
        $jobName = 'test:job:name';

        $this->assertJobProcessorWithNoPreviousJob($jobName);
        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->willReturn(true);

        $result = $this->handler->handle([], $jobName);

        self::assertTrue($result);
    }

    private function assertJobProcessorWithActiveReindexJob(string $jobName): void
    {
        $lastJobId = 123;
        $lastJob = $this->createJobWithId($lastJobId);
        $activeReindexJob = new Job();
        $reindexJobName = $this->getReindexJobName($lastJobId);

        $this->jobProcessor->expects(self::exactly(2))
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturnMap([
                [$jobName, [Job::STATUS_SUCCESS], $lastJob],
                [$reindexJobName, [Job::STATUS_NEW, Job::STATUS_RUNNING], $activeReindexJob],
            ]);
    }

    private function assertJobProcessorWithCompletedReindexJob(string $jobName): void
    {
        $lastJobId = 123;
        $lastJob = $this->createJobWithId($lastJobId);
        $reindexJobName = $this->getReindexJobName($lastJobId);

        $this->jobProcessor->expects(self::exactly(2))
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturnMap([
                [$jobName, [Job::STATUS_SUCCESS], $lastJob],
                [$reindexJobName, [Job::STATUS_NEW, Job::STATUS_RUNNING], null],
            ]);
    }

    private function assertJobProcessorWithNoPreviousJob(string $jobName): void
    {
        $this->jobProcessor->expects(self::once())
            ->method('findRootJobByJobNameAndStatuses')
            ->with($jobName, [Job::STATUS_SUCCESS])
            ->willReturn(null);
    }

    private function createJobWithId(int $id): Job
    {
        $job = new Job();
        $job->setId($id);

        return $job;
    }

    private function getReindexJobName(int $jobId): string
    {
        return sprintf('%s:%s', ReindexRequestItemProductsByRelatedJobIdTopic::NAME, $jobId);
    }
}
