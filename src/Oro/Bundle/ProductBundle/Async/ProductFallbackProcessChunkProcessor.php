<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Async;

use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackProcessChunkTopic;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Processes a chunk of products to populate fallback values.
 */
class ProductFallbackProcessChunkProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private JobRunner $jobRunner,
        private ProductFallbackUpdateManager $updateManager
    ) {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [ProductFallbackProcessChunkTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();
        $jobId = $body[ProductFallbackProcessChunkTopic::JOB_ID];
        $productIds = $body[ProductFallbackProcessChunkTopic::PRODUCT_IDS];

        try {
            $result = $this->jobRunner->runDelayed(
                $jobId,
                function () use ($productIds) {
                    $this->updateManager->processChunk($productIds);

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to process product fallback chunk. '
                . 'The processor was unable to update fallback field values for a batch of products. '
                . 'The products in this chunk may have incomplete fallback data. '
                . 'To retry, run the command: bin/console oro:platform:post-upgrade-tasks --task=product_fallback',
                [
                    'exception' => $exception,
                    'productIds' => $productIds ?? [],
                ]
            );

            return self::REJECT;
        }
    }
}
