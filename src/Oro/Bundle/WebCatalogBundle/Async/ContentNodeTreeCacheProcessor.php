<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\Dumper\ContentNodeTreeDumper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class ContentNodeTreeCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ContentNodeTreeDumper
     */
    private $dumper;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @param JobRunner $jobRunner
     * @param ContentNodeTreeDumper $dumper
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobRunner $jobRunner,
        ContentNodeTreeDumper $dumper,
        ManagerRegistry $registry,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->dumper = $dumper;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runDelayed($data['jobId'], function () use ($data, $message) {
            try {
                if (empty($data['scope'])) {
                    $this->logger->error(
                        'Message is invalid. Key "scope" was not found.',
                        ['message' => $message->getBody()]
                    );

                    return false;
                }
                $scope = $this->registry
                    ->getManagerForClass(Scope::class)
                    ->find(Scope::class, $data['scope']);

                if (empty($data['contentNode'])) {
                    $this->logger->error(
                        'Message is invalid. Key "contentNode" was not found.',
                        ['message' => $message->getBody()]
                    );

                    return false;
                }
                $contentNode = $this->registry
                    ->getManagerForClass(ContentNode::class)
                    ->find(ContentNode::class, $data['contentNode']);

                $this->dumper->dump($contentNode, $scope);
            } catch (\Exception $e) {
                $this->logger->error(
                    'Unexpected exception occurred during queue message processing',
                    [
                        'message' => $message->getBody(),
                        'topic' => Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                        'exception' => $e
                    ]
                );

                return false;
            }

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE];
    }
}
