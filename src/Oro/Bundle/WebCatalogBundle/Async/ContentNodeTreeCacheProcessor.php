<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeTreeCacheTopic as Topic;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCacheDumper;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Dumps content node cache
 * Resets cache for cached node items
 */
class ContentNodeTreeCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const JOB_ID = 'jobId';
    const CONTENT_NODE = 'contentNode';
    const SCOPE = 'scope';

    private JobRunner $jobRunner;

    private ContentNodeTreeCacheDumper $dumper;

    private ManagerRegistry $registry;

    private CacheProvider $layoutCacheProvider;

    public function __construct(
        JobRunner $jobRunner,
        ContentNodeTreeCacheDumper $dumper,
        ManagerRegistry $registry,
        LoggerInterface $logger,
        CacheProvider $layoutCacheProvider
    ) {
        $this->jobRunner = $jobRunner;
        $this->dumper = $dumper;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->layoutCacheProvider = $layoutCacheProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $message->getBody();
        $result = $this->jobRunner->runDelayed(
            $messageBody[Topic::JOB_ID],
            function () use ($messageBody) {
                $this->dumper->dump(
                    $messageBody[Topic::CONTENT_NODE],
                    $messageBody[Topic::SCOPE]
                );

                // Remove all cached layout data provider web catalog node items
                $this->layoutCacheProvider->deleteAll();

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topic::getName()];
    }
}
