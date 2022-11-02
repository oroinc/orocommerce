<?php

namespace Oro\Bundle\ProductBundle\Async;

use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\ProductBundle\Storage\ProductWebsiteReindexRequestDataStorageInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Provider\SegmentSnapshotDeltaProvider;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * MQ Processor that collects information about added or removed products from product collection
 * to intermediate Product Website Reindex Request storage that will be processed later in dependent job,
 * to prevent duplicate requests on reindex.
 */
class ReindexProductCollectionProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    private JobRunner $jobRunner;

    private SegmentMessageFactory $messageFactory;

    private SegmentSnapshotDeltaProvider $segmentSnapshotDeltaProvider;

    private ProductWebsiteReindexRequestDataStorageInterface $websiteReindexRequestDataStorage;

    public function __construct(
        JobRunner $jobRunner,
        SegmentMessageFactory $messageFactory,
        SegmentSnapshotDeltaProvider $segmentSnapshotDeltaProvider,
        ProductWebsiteReindexRequestDataStorageInterface $websiteReindexRequestDataStorage
    ) {
        $this->jobRunner = $jobRunner;
        $this->messageFactory = $messageFactory;
        $this->segmentSnapshotDeltaProvider = $segmentSnapshotDeltaProvider;
        $this->websiteReindexRequestDataStorage = $websiteReindexRequestDataStorage;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): ?string
    {
        try {
            $body = $message->getBody();
            $jobId = $this->messageFactory->getJobIdFromMessage($body);
            $segment = $this->messageFactory->getSegmentFromMessage($body);
            $websiteIds = $this->messageFactory->getWebsiteIdsFromMessage($body);
            $isFull = $this->messageFactory->getIsFull($body);
            $additionalProducts = $this->messageFactory->getAdditionalProductsFromMessage($body) ?? [];

            $result = $this->jobRunner->runDelayed(
                $jobId,
                function (JobRunner $jobRunner, Job $job) use ($segment, $websiteIds, $isFull, $additionalProducts) {
                    $this->doJob($job, $segment, $websiteIds, $isFull, $additionalProducts);

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                'Queue Message is invalid',
                ['exception' => $e]
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during segment product collection reindexation',
                [
                    'topic' => ReindexProductCollectionBySegmentTopic::getName(),
                    'exception' => $e
                ]
            );

            return self::REJECT;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [ReindexProductCollectionBySegmentTopic::getName()];
    }

    private function doJob(
        Job $childJob,
        Segment $segment,
        array $websiteIds,
        bool $isFull,
        array $additionalProducts
    ): void {
        $relatedJobId = $childJob->getRootJob()->getId();
        foreach ($this->getAllProductIdsForReindex($segment, $isFull) as $batch) {
            $batch = array_diff($batch, $additionalProducts);
            if (empty($batch)) {
                continue;
            }

            $this->websiteReindexRequestDataStorage->insertMultipleRequests(
                $relatedJobId,
                $websiteIds,
                $batch
            );
        }

        if ($additionalProducts) {
            $this->websiteReindexRequestDataStorage->insertMultipleRequests(
                $relatedJobId,
                $websiteIds,
                $additionalProducts
            );
        }
    }

    private function getAllProductIdsForReindex(Segment $segment, bool $isFull): \Generator
    {
        if ($isFull) {
            foreach ($this->segmentSnapshotDeltaProvider->getAllEntityIds($segment) as $batch) {
                yield array_map(static fn ($batch) => reset($batch), $batch);
            }
        } else {
            foreach ($this->segmentSnapshotDeltaProvider->getAddedEntityIds($segment) as $batch) {
                yield array_map(static fn ($batch) => reset($batch), $batch);
            }
        }

        if ($segment->getId()) {
            foreach ($this->segmentSnapshotDeltaProvider->getRemovedEntityIds($segment) as $batch) {
                yield array_map(static fn ($batch) => reset($batch), $batch);
            }
        }
    }
}
