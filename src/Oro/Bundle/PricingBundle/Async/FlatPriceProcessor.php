<?php

namespace Oro\Bundle\PricingBundle\Async;

use Oro\Bundle\MessageQueueBundle\Compatibility\TopicAwareTrait;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Responsible for updating the flat product price index based on price lists or specific products.
 */
class FlatPriceProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait, TopicAwareTrait;

    private int $productsBatchSize = 500;

    private MessageProducerInterface $producer;
    private JobRunner $jobRunner;

    public function __construct(MessageProducerInterface $producer, JobRunner $jobRunner)
    {
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
    }

    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $body = $this->getResolvedBody($message);
            $products = $body['products'];

            $closure = fn (JobRunner $jobRunner, Job $job) => $this->doJob($jobRunner, $job, $products);
            $name = sprintf('%s_%s', ResolveFlatPriceTopic::getName(), UUIDGenerator::v4());

            return $this->jobRunner->runUnique($message->getMessageId(), $name, $closure) ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                ['exception' => $e, 'topic' => ResolveFlatPriceTopic::NAME]
            );

            return self::REJECT;
        }
    }

    private function doJob(JobRunner $jobRunner, Job $job, array $products): bool
    {
        $products = array_chunk($products, $this->productsBatchSize);
        foreach ($products as $batchIndex => $batch) {
            $this->sendToReindex($jobRunner, $job, $batch, $batchIndex);
        }

        return true;
    }

    protected function sendToReindex(JobRunner $jobRunner, Job $job, array $productsIds, int $batchId): void
    {
        $jobRunner->createDelayed(
            sprintf('%s_batch_%d', $job->getName(), $batchId),
            function (JobRunner $jobRunner, Job $child) use ($productsIds) {
                $message = new Message(
                    [
                        'jobId' => $child->getId(),
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productsIds,
                            AbstractIndexer::CONTEXT_FIELD_GROUPS => ['pricing']
                        ]
                    ],
                    AsyncIndexer::DEFAULT_PRIORITY_REINDEX
                );
                $this->producer->send(AsyncIndexer::TOPIC_REINDEX, $message);

                return true;
            }
        );
    }

    public function setProductsBatchSize(int $productsBatchSize): void
    {
        $this->productsBatchSize = $productsBatchSize;
    }

    public static function getSubscribedTopics(): array
    {
        return [ResolveFlatPriceTopic::getName()];
    }
}
