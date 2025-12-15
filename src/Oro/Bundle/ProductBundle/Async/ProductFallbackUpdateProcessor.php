<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Async;

use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackFinalizeTopic;
use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackProcessChunkTopic;
use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackUpdateTopic;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Schedules product fallback update jobs for processing.
 */
class ProductFallbackUpdateProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private JobRunner $jobRunner,
        private MessageProducerInterface $producer,
        private ProductFallbackUpdateManager $updateManager,
        private DependentJobService $dependentJobService
    ) {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [ProductFallbackUpdateTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();
        $batchSize = $body[ProductFallbackUpdateTopic::BATCH_SIZE_OPTION];

        $this->logger->notice('Started fixing product entity field fallback values');

        try {
            $result = $this->jobRunner->runUniqueByMessage(
                $message,
                function (JobRunner $jobRunner, Job $job) use ($batchSize) {
                    $this->scheduleChunks($jobRunner, $job, $batchSize);
                    $this->scheduleFinalizeJob($job);

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to schedule product fallback update jobs. '
                . 'The processor was unable to create background jobs for updating product fallback fields. '
                . 'No products were processed in this batch. '
                . 'To retry, run the command: bin/console oro:platform:post-upgrade-tasks --task=product_fallback',
                [
                    'exception' => $exception,
                    'batchSize' => $batchSize,
                ]
            );

            return self::REJECT;
        }
    }

    private function scheduleChunks(JobRunner $jobRunner, Job $job, int $batchSize): bool
    {
        $chunkIndex = 0;
        $hasChunks = false;

        foreach ($this->updateManager->getProductIdChunks($batchSize) as $productIds) {
            $hasChunks = true;
            $this->scheduleChunkJob($jobRunner, $job, $chunkIndex++, $productIds);
        }

        if (!$hasChunks) {
            $this->logger->info('No product fallback updates were required.');
        }

        return $hasChunks;
    }

    private function scheduleChunkJob(JobRunner $jobRunner, Job $job, int $chunkIndex, array $productIds): void
    {
        $jobRunner->createDelayed(
            sprintf('%s:%d', $job->getName(), $chunkIndex),
            function (JobRunner $jobRunner, Job $child) use ($productIds) {
                $this->producer->send(
                    ProductFallbackProcessChunkTopic::getName(),
                    [
                        ProductFallbackProcessChunkTopic::JOB_ID => $child->getId(),
                        ProductFallbackProcessChunkTopic::PRODUCT_IDS => $productIds,
                    ]
                );
            }
        );
    }

    private function scheduleFinalizeJob(Job $job): void
    {
        $context = $this->dependentJobService->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            ProductFallbackFinalizeTopic::getName(),
            [
                ProductFallbackFinalizeTopic::JOB_ID => $job->getRootJob()->getId(),
            ]
        );
        $this->dependentJobService->saveDependentJob($context);
    }
}
