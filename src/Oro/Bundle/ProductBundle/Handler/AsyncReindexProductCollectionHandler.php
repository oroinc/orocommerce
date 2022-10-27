<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Handler;

use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Bundle\ProductBundle\Exception\FailedToRunReindexProductCollectionJobException;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
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
    private SegmentMessageFactory $messageFactory;

    public function __construct(
        JobRunner $jobRunner,
        DependentJobService $dependentJobService,
        MessageProducerInterface $messageProducer,
        SegmentMessageFactory $messageFactory
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
        bool $throwExceptionOnFailToRunJob = false,
        array $indexationFieldGroups = null
    ): bool {
        $runCallback = fn (JobRunner $jobRunner, Job $job) => $this->doJob(
            $jobRunner,
            $job,
            $childJobPartialMessages,
            $indexationFieldGroups
        );

        $result = $this->jobRunner->runUnique(UUIDGenerator::v4(), $uniqueJobName, $runCallback);

        if ($result === null && $throwExceptionOnFailToRunJob) {
            throw new FailedToRunReindexProductCollectionJobException($uniqueJobName);
        }

        return (bool)$result;
    }

    private function doJob(
        JobRunner $jobRunner,
        Job $job,
        iterable $childJobPartialMessages,
        array $indexationFieldGroups = null
    ): bool {
        $isDependentJobAdded = false;
        $childJobMessageTopic = ReindexProductCollectionBySegmentTopic::NAME;
        foreach ($childJobPartialMessages as $childJobPartialMessage) {
            if (!$isDependentJobAdded) {
                $this->addDependentJob($job->getRootJob(), $indexationFieldGroups);
                $isDependentJobAdded = true;
            }

            $jobRunner->createDelayed(
                ReindexProductCollectionBySegmentTopic::NAME . ':' . UUIDGenerator::v4(),
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

    private function addDependentJob(Job $rootJob, array $indexationFieldGroups = null): void
    {
        $dependentJobContext = $this->dependentJobService->createDependentJobContext($rootJob);
        $dependentJobContext->addDependentJob(
            ReindexRequestItemProductsByRelatedJobIdTopic::NAME,
            [
                'relatedJobId' => $rootJob->getId(),
                'indexationFieldsGroups' => $indexationFieldGroups
            ]
        );

        $this->dependentJobService->saveDependentJob($dependentJobContext);
    }
}
