<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic as Topic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Initiates web catalog cache calculation always starting from the root content node.
 */
class WebCatalogCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private JobRunner $jobRunner;

    private MessageProducerInterface $producer;

    private ManagerRegistry $registry;

    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ManagerRegistry $registry
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->registry = $registry;

        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function () use ($messageBody) {
                $rootNodeId = $this->registry
                    ->getRepository(ContentNode::class)
                    ->getRootNodeIdByWebCatalog($messageBody[Topic::WEB_CATALOG_ID]);

                if (!$rootNodeId) {
                    $this->logger->error('Root node for the web catalog #{webCatalogId} is not found', $messageBody);
                    return false;
                }

                $this->producer->send(
                    WebCatalogCalculateContentNodeCacheTopic::getName(),
                    [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => $rootNodeId]
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
