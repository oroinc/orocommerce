<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Bundle\SEOBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SEOBundle\Model\SitemapIndexMessageFactory;
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
     * @param SitemapIndexMessageFactory $messageFactory
     * @param SitemapDumperInterface $dumper
     * @param LoggerInterface $logger
     */
    public function __construct(
        SitemapIndexMessageFactory $messageFactory,
        SitemapDumperInterface $dumper,
        LoggerInterface $logger
    ) {
        $this->messageFactory = $messageFactory;
        $this->dumper = $dumper;
        $this->logger = $logger;
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
