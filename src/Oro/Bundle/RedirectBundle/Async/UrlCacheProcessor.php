<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheTopic;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Fill Slug URL caches with data received for a given set of entities.
 */
class UrlCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private JobRunner $jobRunner;

    private MessageFactoryInterface $messageFactory;

    private SluggableUrlDumper $dumper;

    public function __construct(
        JobRunner $jobRunner,
        MessageFactoryInterface $messageFactory,
        SluggableUrlDumper $dumper,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->messageFactory = $messageFactory;
        $this->dumper = $dumper;
        $this->logger = $logger;
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageData = $message->getBody();
        $entities = $this->messageFactory->getEntitiesFromMessage($messageData);

        if (isset($messageData[CalculateSlugCacheTopic::JOB_ID])) {
            $result = $this->jobRunner->runDelayed(
                $messageData[CalculateSlugCacheTopic::JOB_ID],
                function () use ($entities) {
                    foreach ($entities as $entity) {
                        $this->dumper->dump($entity);
                    }

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
        }

        foreach ($entities as $entity) {
            $this->dumper->dump($entity);
        }

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [CalculateSlugCacheTopic::getName()];
    }
}
