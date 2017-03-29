<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Bundle\SEOBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SEOBundle\Model\SitemapMessageFactory;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Psr\Log\LoggerInterface;

class GenerateSitemapByWebsiteAndTypeProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SitemapDumperInterface
     */
    private $sitemapDumper;

    /**
     * @var SitemapMessageFactory
     */
    private $messageFactory;

    /**
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     * @param SitemapDumperInterface $sitemapDumper
     * @param SitemapMessageFactory $messageFactory
     */
    public function __construct(
        JobRunner $jobRunner,
        LoggerInterface $logger,
        SitemapDumperInterface $sitemapDumper,
        SitemapMessageFactory $messageFactory
    ) {
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
        $this->sitemapDumper = $sitemapDumper;
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        try {
            $jobId = $this->messageFactory->getJobIdFromMessage($data);
            $result = $this->jobRunner->runDelayed($jobId, function () use ($data, $message) {
                try {
                    $this->sitemapDumper->dump(
                        $this->messageFactory->getWebsiteFromMessage($data),
                        $this->messageFactory->getVersionFromMessage($data),
                        $this->messageFactory->getTypeFromMessage($data)
                    );
                } catch (InvalidArgumentException $e) {
                    $this->logger->error(
                        'Queue Message is invalid',
                        [
                            'exception' => $e,
                            'message' => $message->getBody()
                        ]
                    );

                    return false;
                } catch (\Exception $exception) {
                    $this->logger->error(
                        'Unexpected exception occurred during queue message processing',
                        [
                            'message' => $message->getBody(),
                            'exception' => $exception,
                            'topic' => Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE
                        ]
                    );

                    return false;
                }

                return true;
            });
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                'Queue Message does not contain correct jobId',
                [
                    'exception' => $e,
                    'message' => $message->getBody()
                ]
            );

            return self::REJECT;
        }

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE];
    }
}
