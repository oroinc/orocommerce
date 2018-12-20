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

/**
 * Schedule cache recalculation for web catalogs
 */
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

            $result = $this->jobRunner->runUnique(
                $message->getMessageId(),
                Topics::CALCULATE_WEB_CATALOG_CACHE,
                function (JobRunner $jobRunner) use ($webCatalogId) {
                    foreach ($this->getWebCatalogs($webCatalogId) as $webCatalog) {
                        $this->scheduleCacheRecalculationForWebCatalog($jobRunner, $webCatalog);
                    }

                    return true;
                }
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'topic' => Topics::CALCULATE_WEB_CATALOG_CACHE,
                    'exception' => $e
                ]
            );

            return self::REJECT;
        }

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param int|array|null $webCatalogId
     * @return array
     */
    protected function getWebCatalogs($webCatalogId): array
    {
        $repository = $this->registry
            ->getManagerForClass(WebCatalog::class)
            ->getRepository(WebCatalog::class);

        if ($webCatalogId) {
            return $repository->findBy(['id' => $webCatalogId]);
        }

        return $repository->findAll();
    }

    /**
     * @param JobRunner $jobRunner
     * @param WebCatalog $webCatalog
     */
    protected function scheduleCacheRecalculationForWebCatalog(JobRunner $jobRunner, WebCatalog $webCatalog)
    {
        $nodes = $this->getAllNodesByWebCatalog($webCatalog);

        if (!$nodes) {
            return;
        }
        $scopes = $this->scopeMatcher->getUsedScopes($webCatalog);

        foreach ($scopes as $scope) {
            foreach ($nodes as $node) {
                $jobRunner->createDelayed(
                    sprintf(
                        '%s:%s:%s',
                        Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                        $webCatalog->getId(),
                        $scope->getId()
                    ),
                    function (JobRunner $jobRunner, Job $child) use ($node, $scope) {
                        $this->producer->send(
                            Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE,
                            [
                                'contentNode' => $node->getId(),
                                'scope'       => $scope->getId(),
                                'jobId'       => $child->getId(),
                            ]
                        );
                    }
                );
            }
        }
    }

    /**
     * @param WebCatalog $webCatalog
     * @return ContentNode[]
     */
    protected function getAllNodesByWebCatalog(WebCatalog $webCatalog): array
    {
        /** @var ContentNodeRepository $contentNodeRepo */
        $contentNodeRepo = $this->registry
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);

        return $contentNodeRepo->findBy(['webCatalog' => $webCatalog]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CALCULATE_WEB_CATALOG_CACHE];
    }
}
