<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Bundle\SEOBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SEOBundle\Model\SitemapIndexMessageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\SEO\Tools\SitemapDumperInterface;

use Psr\Log\LoggerInterface;

class GenerateSitemapIndexProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var SitemapIndexMessageFactory
     */
    private $messageFactory;

    /**
     * @var SitemapDumperInterface
     */
    private $dumper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SitemapFilesystemAdapter
     */
    private $filesystemAdapter;

    /**
     * @param SitemapIndexMessageFactory $messageFactory
     * @param SitemapDumperInterface $dumper
     * @param LoggerInterface $logger
     * @param SitemapFilesystemAdapter $filesystemAdapter
     */
    public function __construct(
        SitemapIndexMessageFactory $messageFactory,
        SitemapDumperInterface $dumper,
        LoggerInterface $logger,
        SitemapFilesystemAdapter $filesystemAdapter
    ) {
        $this->messageFactory = $messageFactory;
        $this->dumper = $dumper;
        $this->logger = $logger;
        $this->filesystemAdapter = $filesystemAdapter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = JSON::decode($message->getBody());

        try {
            $website = $this->messageFactory->getWebsiteFromMessage($messageBody);
            $version = $this->messageFactory->getVersionFromMessage($messageBody);

            $this->dumper->dump($website, $version);
            $this->filesystemAdapter->makeNewerVersionActual($website, $version);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                'Queue Message is invalid',
                [
                    'exception' => $e,
                    'message' => $message->getBody()
                ]
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'exception' => $e,
                    'message' => $message->getBody(),
                    'topic' => Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE,
                ]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE];
    }
}
