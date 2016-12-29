<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ScopeMatcher;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class WebCatalogCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param ScopeMatcher $scopeMatcher
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ScopeMatcher $scopeMatcher,
        ManagerRegistry $registry,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->scopeMatcher = $scopeMatcher;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $webCatalogId = JSON::decode($message->getBody());
            $webCatalog = $this->registry
                ->getManagerForClass(WebCatalog::class)
                ->find(WebCatalog::class, $webCatalogId);

            /** @var ContentNodeRepository $contentNodeRepo */
            $contentNodeRepo = $this->registry
                ->getManagerForClass(ContentNode::class)
                ->getRepository(ContentNode::class);
            $rootNode = $contentNodeRepo->getRootNodeByWebCatalog($webCatalog);

            $result = $this->jobRunner->runUnique(
                $message->getMessageId(),
                Topics::CALCULATE_WEB_CATALOG_CACHE,
                function (JobRunner $jobRunner) use ($webCatalog, $rootNode) {
                    $scopes = $this->scopeMatcher->getUsedScopes($webCatalog);

                    foreach ($scopes as $scope) {
                        $jobRunner->createDelayed(
                            sprintf(
                                '%s:%s:%s',
                                Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                                $webCatalog->getId(),
                                $scope->getId()
                            ),
                            function (JobRunner $jobRunner, Job $child) use ($rootNode, $scope) {
                                $this->producer->send(Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE, [
                                    'contentNode' => $rootNode->getId(),
                                    'scope' => $scope->getId(),
                                    'jobId' => $child->getId(),
                                ]);
                            }
                        );
                    }

                    return true;
                }
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'message' => $message->getBody(),
                    'topic' => Topics::CALCULATE_WEB_CATALOG_CACHE,
                    'exception' => $e
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
        return [Topics::CALCULATE_WEB_CATALOG_CACHE];
    }
}
