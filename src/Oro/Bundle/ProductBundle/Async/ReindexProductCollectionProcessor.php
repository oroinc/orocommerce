<?php

namespace Oro\Bundle\ProductBundle\Async;

use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Provider\SegmentSnapshotDeltaProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * MQ Processor that dispatches search reindexation event for added or removed products from product collection.
 */
class ReindexProductCollectionProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ReindexMessageGranularizer
     */
    private $reindexMessageGranularizer;

    /**
     * @var SegmentMessageFactory
     */
    private $messageFactory;

    /**
     * @var SegmentSnapshotDeltaProvider
     */
    private $segmentSnapshotDeltaProvider;

    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        ReindexMessageGranularizer $reindexMessageGranularizer,
        SegmentMessageFactory $messageFactory,
        SegmentSnapshotDeltaProvider $segmentSnapshotDeltaProvider
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->reindexMessageGranularizer = $reindexMessageGranularizer;
        $this->messageFactory = $messageFactory;
        $this->segmentSnapshotDeltaProvider = $segmentSnapshotDeltaProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $body = $message->getBody();
            $segment = $this->messageFactory->getSegment($body);
            $websiteIds = $body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS];
            $isFull = $body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL];
            $additionalProducts = $body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS];
            $jobName = $this->getUniqueJobName($segment, $websiteIds);
            $result = $this->jobRunner->runUnique(
                $message->getMessageId(),
                $jobName,
                function (JobRunner $jobRunner, Job $job) use ($segment, $websiteIds, $isFull, $additionalProducts) {
                    $index = 0;
                    foreach ($this->getAllProductIdsForReindex($segment, $isFull) as $batch) {
                        $additionalProducts = array_diff($additionalProducts, $batch);
                        $this->sendToReindex($jobRunner, $job, $batch, $websiteIds, $index);
                    }

                    if ($additionalProducts) {
                        $this->sendToReindex($jobRunner, $job, $additionalProducts, $websiteIds, $index);
                    }

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
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
    public static function getSubscribedTopics()
    {
        return [ReindexProductCollectionBySegmentTopic::getName()];
    }

    /**
     * @param Segment $segment
     * @param bool $isFull
     * @return \Generator
     */
    private function getAllProductIdsForReindex(Segment $segment, $isFull)
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

    /**
     * @param JobRunner $jobRunner
     * @param Job $job
     * @param array $batch
     * @param array $websiteIds
     * @param int $index
     */
    private function sendToReindex(JobRunner $jobRunner, Job $job, array $batch, array $websiteIds, &$index)
    {
        $reindexMsgData = $this->reindexMessageGranularizer->process(
            [Product::class],
            $websiteIds,
            [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $batch]
        );

        foreach ($reindexMsgData as $msgData) {
            $jobRunner->createDelayed(
                sprintf('%s:reindex:%s', $job->getName(), ++$index),
                function (JobRunner $jobRunner, Job $child) use ($msgData) {
                    $msgData['jobId'] = $child->getId();
                    $message = new Message($msgData, AsyncIndexer::DEFAULT_PRIORITY_REINDEX);
                    $this->producer->send(AsyncIndexer::TOPIC_REINDEX, $message);
                }
            );
        }
    }

    /**
     * @param Segment $segment
     * @param array $websiteIds
     * @return string
     */
    private function getUniqueJobName(Segment $segment, $websiteIds): string
    {
        sort($websiteIds);

        return sprintf(
            '%s:%s',
            ReindexProductCollectionBySegmentTopic::getName(),
            md5($segment->getDefinition()) . ':' . md5(implode($websiteIds))
        );
    }
}
