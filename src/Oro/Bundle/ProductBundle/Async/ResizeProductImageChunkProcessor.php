<?php

namespace Oro\Bundle\ProductBundle\Async;

use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageChunkTopic;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Processes chunks resize requests using run delayed job with sending individual product image resize messages.
 */
class ResizeProductImageChunkProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface
{
    public function __construct(
        protected JobRunner $jobRunner,
        protected MessageProducerInterface $messageProducer,
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        $result = $this->jobRunner->runDelayed(
            $body['jobId'],
            function () use ($body) {
                $dimensions = $body[ResizeProductImageChunkTopic::DIMENSIONS];
                foreach ($body[ResizeProductImageChunkTopic::IMAGE_IDS] as $imageId) {
                    $this->messageProducer->send(
                        ResizeProductImageTopic::getName(),
                        [
                            ResizeProductImageTopic::FORCE_OPTION => $body[ResizeProductImageChunkTopic::FORCE],
                            ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => $imageId,
                            ResizeProductImageTopic::DIMENSIONS_OPTION => $dimensions,
                        ]
                    );
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [ResizeProductImageChunkTopic::getName()];
    }
}
