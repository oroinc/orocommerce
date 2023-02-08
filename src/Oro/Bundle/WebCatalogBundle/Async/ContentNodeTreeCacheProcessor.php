<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeTreeCacheTopic as Topic;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCacheDumper;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Dumps content node cache
 * Resets cache for cached node items
 */
class ContentNodeTreeCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private JobRunner $jobRunner;

    private ContentNodeTreeCacheDumper $dumper;

    public function __construct(
        JobRunner $jobRunner,
        ContentNodeTreeCacheDumper $dumper
    ) {
        $this->jobRunner = $jobRunner;
        $this->dumper = $dumper;

        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();
        $result = $this->jobRunner->runDelayed(
            $messageBody[Topic::JOB_ID],
            function () use ($messageBody) {
                $this->dumper->dump(
                    $messageBody[Topic::CONTENT_NODE],
                    $messageBody[Topic::SCOPE]
                );

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    public static function getSubscribedTopics(): array
    {
        return [Topic::getName()];
    }
}
