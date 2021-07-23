<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Bundle\SEOBundle\Sitemap\Filesystem\PublicSitemapFilesystemAdapter;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Moves generated sitemap and robots.txt file for a website from a temporary storage to Gaufrette storage.
 */
class MoveGeneratedSitemapsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const WEBSITE_IDS = 'websiteIds';
    private const VERSION     = 'version';

    /** @var PublicSitemapFilesystemAdapter */
    private $fileSystemAdapter;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        PublicSitemapFilesystemAdapter $fileSystemAdapter,
        LoggerInterface $logger
    ) {
        $this->fileSystemAdapter = $fileSystemAdapter;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SITEMAP_MOVE_GENERATED_FILES];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $this->resolveMessage($message);
        if (null === $body) {
            return self::REJECT;
        }

        try {
            $this->fileSystemAdapter->moveSitemaps($body[self::WEBSITE_IDS]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during moving the generated sitemaps.',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    private function resolveMessage(MessageInterface $message): ?array
    {
        try {
            return $this->getMessageResolver()->resolve(JSON::decode($message->getBody()));
        } catch (\Throwable $e) {
            $this->logger->critical('Got invalid message.', ['exception' => $e]);
        }

        return null;
    }

    private function getMessageResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([self::VERSION, self::WEBSITE_IDS]);
        $resolver->setAllowedTypes(self::VERSION, ['int']);
        $resolver->setAllowedTypes(self::WEBSITE_IDS, ['array']);

        return $resolver;
    }
}
