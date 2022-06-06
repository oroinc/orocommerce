<?php

namespace Oro\Bundle\ProductBundle\Async;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Storage\ProductWebsiteReindexRequestDataStorageInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * MQ Processor that dispatches search reindexation event for all records that found by given relatedJobId.
 */
class ReindexRequestItemProductsByRelatedJobProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface
{
    private const BATCH_SIZE = 100;

    private ProductWebsiteReindexRequestDataStorageInterface $websiteReindexRequestDataStorage;
    private JobRunner $jobRunner;
    private MessageProducerInterface $producer;
    private LoggerInterface $logger;
    private int $batchSize = self::BATCH_SIZE;

    public function __construct(
        ProductWebsiteReindexRequestDataStorageInterface $websiteReindexRequestDataStorage,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger
    ) {
        $this->websiteReindexRequestDataStorage = $websiteReindexRequestDataStorage;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $body = JSON::decode($message->getBody());
            if (!isset($body['relatedJobId'])) {
                throw new InvalidArgumentException();
            }

            $relatedJobId = (int) $body['relatedJobId'];
            $fieldGroups = $body['indexationFieldsGroups'];
            $websiteIdsOnReindex = $this->websiteReindexRequestDataStorage->getWebsiteIdsByRelatedJobId(
                $relatedJobId
            );

            if (empty($websiteIdsOnReindex)) {
                return self::ACK;
            }

            $jobName = $this->getUniqueJobName($relatedJobId);
            $result = $this->jobRunner->runUnique(
                $message->getMessageId(),
                $jobName,
                function (JobRunner $jobRunner, Job $job) use ($relatedJobId, $websiteIdsOnReindex, $fieldGroups) {
                    $this->doJobWithFieldGroups($jobRunner, $job, $relatedJobId, $websiteIdsOnReindex, $fieldGroups);

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'exception' => $e,
                    'topic' => Topics::REINDEX_REQUEST_ITEM_PRODUCTS_BY_RELATED_JOB_ID
                ]
            );

            return self::REJECT;
        }
    }

    protected function doJob(
        JobRunner $jobRunner,
        Job $job,
        int $relatedJobId,
        array $websiteIds
    ): void {
        $this->doJobWithFieldGroups(
            $jobRunner,
            $job,
            $relatedJobId,
            $websiteIds
        );
    }

    protected function doJobWithFieldGroups(
        JobRunner $jobRunner,
        Job $job,
        int $relatedJobId,
        array $websiteIds,
        array $fieldGroups = null
    ): void {
        foreach ($websiteIds as $websiteId) {
            $productIdIteratorOnReindex = $this->websiteReindexRequestDataStorage
                ->getProductIdIteratorByRelatedJobIdAndWebsiteId(
                    $relatedJobId,
                    $websiteId,
                    $this->batchSize
                );

            $batchIndex = 0;
            foreach ($productIdIteratorOnReindex as $productIds) {
                $this->sendToReindexWithFieldGroups(
                    $jobRunner,
                    $job,
                    $websiteId,
                    $productIds,
                    $batchIndex,
                    $fieldGroups
                );

                $this->websiteReindexRequestDataStorage->deleteProcessedRequestItems(
                    $relatedJobId,
                    $websiteId,
                    $productIds
                );

                $batchIndex++;
            }
        }
    }

    protected function sendToReindex(
        JobRunner $jobRunner,
        Job $job,
        int $websiteId,
        array $productIds,
        int $batchId
    ): void {
        $this->sendToReindexWithFieldGroups(
            $jobRunner,
            $job,
            $websiteId,
            $productIds,
            $batchId
        );
    }

    /**
     * @param JobRunner $jobRunner
     * @param Job $job
     * @param int $websiteId
     * @param array $productIds
     * @param int $batchId
     * @return void
     */
    protected function sendToReindexWithFieldGroups(
        JobRunner $jobRunner,
        Job $job,
        int $websiteId,
        array $productIds,
        int $batchId,
        array $fieldGroups = null
    ): void {
        $jobRunner->createDelayed(
            sprintf('%s:reindex:%d:%d', $job->getName(), $websiteId, $batchId),
            function (JobRunner $jobRunner, Job $child) use ($websiteId, $productIds, $fieldGroups) {
                $message = new Message(
                    [
                        'jobId' => $child->getId(),
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [$websiteId],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds,
                            AbstractIndexer::CONTEXT_FIELD_GROUPS => $fieldGroups
                        ]
                    ],
                    AsyncIndexer::DEFAULT_PRIORITY_REINDEX
                );
                $this->producer->send(AsyncIndexer::TOPIC_REINDEX, $message);
            }
        );
    }

    /**
     * @param int $relatedJobId
     * @return string
     */
    private function getUniqueJobName(int $relatedJobId): string
    {
        $jobKey = sprintf(
            '%s:%s',
            Topics::REINDEX_REQUEST_ITEM_PRODUCTS_BY_RELATED_JOB_ID,
            $relatedJobId
        );

        return $jobKey;
    }

    public static function getSubscribedTopics()
    {
        return [Topics::REINDEX_REQUEST_ITEM_PRODUCTS_BY_RELATED_JOB_ID];
    }
}
