<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Handler;

use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\Exception\FailedToRunReindexProductCollectionJobException;
use Oro\Bundle\ProductBundle\Model\AccumulateSegmentMessageFactory;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;

/**
 * This service handles the logic of processing product collection after segment(s) change(s),
 * which uses to prepare all required jobs and send all required messages to MQ.
 */
class AsyncReindexProductCollectionHandler implements AsyncReindexProductCollectionHandlerInterface
{
    private JobRunner $jobRunner;
    private DependentJobService $dependentJobService;
    private MessageProducerInterface $messageProducer;
    private AccumulateSegmentMessageFactory $messageFactory;

    public function __construct(
        JobRunner $jobRunner,
        DependentJobService $dependentJobService,
        MessageProducerInterface $messageProducer,
        AccumulateSegmentMessageFactory $messageFactory
    ) {
        $this->jobRunner = $jobRunner;
        $this->dependentJobService = $dependentJobService;
        $this->messageProducer = $messageProducer;
        $this->messageFactory = $messageFactory;
    }

    /**
     * Return 'true' in case when unique job successfully run and
     * reindex product collection child jobs and dependent job scheduled,
     * 'false' when failed to run job or throws exception.
     *
     * @param iterable $childJobPartialMessages
     * @param string $uniqueJobName
     * @param bool $throwExceptionOnFailToRunJob
     * @return bool
     * @throws FailedToRunReindexProductCollectionJobException
     */
    public function handle(
        iterable $childJobPartialMessages,
        string $uniqueJobName,
        bool $throwExceptionOnFailToRunJob = false
    ): bool {
        $runCallback = fn (JobRunner $jobRunner, Job $job) => $this->doJob(
            $jobRunner,
            $job,
            $childJobPartialMessages
        );

        $result = $this->jobRunner->runUnique(UUIDGenerator::v4(), $uniqueJobName, $runCallback);

        if ($result === null && $throwExceptionOnFailToRunJob) {
            throw new FailedToRunReindexProductCollectionJobException($uniqueJobName);
        }

        return (bool) $result;
    }

    private function doJob(JobRunner $jobRunner, Job $job, iterable $childJobPartialMessages): bool
    {
        $isDependentJobAdded = false;
        $childJobMessageTopic = Topics::ACCUMULATE_REINDEX_PRODUCT_COLLECTION_BY_SEGMENT;
        foreach ($childJobPartialMessages as $childJobPartialMessage) {
            if (!$isDependentJobAdded) {
                $this->addDependentJob($job->getRootJob());
                $isDependentJobAdded = true;
            }

            $jobRunner->createDelayed(
                Topics::ACCUMULATE_REINDEX_PRODUCT_COLLECTION_BY_SEGMENT . ':' . UUIDGenerator::v4(),
                function (JobRunner $jobRunner, Job $child) use ($childJobPartialMessage, $childJobMessageTopic) {
                    $this->messageProducer->send(
                        $childJobMessageTopic,
                        new Message(
                            $this->messageFactory->createMessageFromJobIdAndPartialMessage(
                                $child->getId(),
                                $childJobPartialMessage
                            )
                        )
                    );
                }
            );
        }

        return true;
    }

    private function addDependentJob(Job $rootJob): void
    {
        $dependentJobContext = $this->dependentJobService->createDependentJobContext($rootJob);
        $dependentJobContext->addDependentJob(
            Topics::REINDEX_REQUEST_ITEM_PRODUCTS_BY_RELATED_JOB_ID,
            ['relatedJobId' => $rootJob->getId()]
        );

        $this->dependentJobService->saveDependentJob($dependentJobContext);
    }
}
