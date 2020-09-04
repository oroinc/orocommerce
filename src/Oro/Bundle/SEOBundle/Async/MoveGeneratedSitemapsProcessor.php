<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Bundle\SEOBundle\Sitemap\Filesystem\GaufretteFilesystemAdapter;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * MQ processor that moves generated sitemaps and robots txt files to the Gaufrette storage.
 */
class MoveGeneratedSitemapsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var GaufretteFilesystemAdapter */
    private $fileSystemAdapter;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param GaufretteFilesystemAdapter $fileSystemAdapter
     * @param LoggerInterface            $logger
     */
    public function __construct(
        GaufretteFilesystemAdapter $fileSystemAdapter,
        LoggerInterface $logger
    ) {
        $this->fileSystemAdapter = $fileSystemAdapter;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = JSON::decode($message->getBody());
        if (!isset($messageBody['websiteIds']) || !\is_array($messageBody['websiteIds'])) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        try {
            $this->fileSystemAdapter->moveSitemaps($messageBody['websiteIds']);
        } catch (\Exception $e) {
            $this->logger->critical(
                'Unexpected exception occurred during moving of the generated sitemaps.',
                [
                    'exception' => $e,
                    'topic'     => Topics::GENERATE_SITEMAP_MOVE_GENERATED_FILES,
                ]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SITEMAP_MOVE_GENERATED_FILES];
    }
}
