<?php

namespace Oro\Bundle\ProductBundle\Async;

use Oro\Bundle\BatchBundle\Tools\ChunksHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeAllProductImagesTopic;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageChunkTopic;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Processes batch resize requests by granularizing them into chunks with product image id.
 * Sends messages in chunks using delayed job.
 */
class ResizeAllProductImagesProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    protected const CHUNK_SIZE = 100;

    protected int $chunkSize = self::CHUNK_SIZE;

    public function __construct(
        protected JobRunner $jobRunner,
        protected MessageProducerInterface $messageProducer,
        protected DoctrineHelper $doctrineHelper
    ) {
    }

    public function setChunkSize(int $chunkSize): void
    {
        $this->chunkSize = $chunkSize;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [ResizeAllProductImagesTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            ResizeAllProductImagesTopic::getName(),
            function (JobRunner $jobRunner, Job $job) use ($body) {
                $this->scheduleChunks($jobRunner, $job, $body);

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    protected function scheduleChunks(JobRunner $jobRunner, Job $job, array $body): void
    {
        $force = $body[ResizeAllProductImagesTopic::FORCE];
        $chunks = $this->getImageChunks();
        $dimensions = $body[ResizeAllProductImagesTopic::DIMENSIONS];
        foreach ($chunks as $index => $chunk) {
            $jobRunner->createDelayed(
                sprintf('%s:chunk:%s', $job->getName(), $index + 1),
                function (JobRunner $jobRunner, Job $childJob) use ($force, $chunk, $dimensions): void {
                    $this->messageProducer->send(
                        ResizeProductImageChunkTopic::getName(),
                        [
                            ResizeProductImageChunkTopic::JOB_ID => $childJob->getId(),
                            ResizeProductImageChunkTopic::FORCE => $force,
                            ResizeProductImageChunkTopic::IMAGE_IDS => $chunk,
                            ResizeProductImageChunkTopic::DIMENSIONS => $dimensions,
                        ]
                    );
                }
            );
        }
    }

    protected function getImageChunks(): \Generator
    {
        $imagesIterator = $this->doctrineHelper->getEntityRepository(ProductImage::class)
            ->getAllProductImagesIterator();

        return ChunksHelper::splitInChunksByColumn($imagesIterator, $this->chunkSize, 'id');
    }
}
