<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic as Topic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeTreeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Schedule cache recalculation for content node
 */
class ContentNodeCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private JobRunner $jobRunner;

    private MessageProducerInterface $producer;

    private ManagerRegistry $doctrine;

    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ManagerRegistry $doctrine
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->doctrine = $doctrine;
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner) use ($messageBody) {
                $contentNode = $this->getContentNode($messageBody[Topic::CONTENT_NODE_ID]);
                if ($contentNode) {
                    $this->scheduleCacheRecalculationForContentNodeTree($jobRunner, $contentNode);
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function scheduleCacheRecalculationForContentNodeTree(JobRunner $jobRunner, ContentNode $contentNode): void
    {
        $scopes = $this->getUsedScopes($contentNode->getWebCatalog());
        foreach ($scopes as $scope) {
            $jobRunner->createDelayed(
                sprintf(
                    '%s:%s:%s',
                    WebCatalogCalculateContentNodeTreeCacheTopic::getName(),
                    $scope->getId(),
                    $contentNode->getId()
                ),
                function (JobRunner $jobRunner, Job $child) use ($contentNode, $scope) {
                    $this->producer->send(
                        WebCatalogCalculateContentNodeTreeCacheTopic::getName(),
                        [
                            WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => $contentNode->getId(),
                            WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => $scope->getId(),
                            WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => $child->getId(),
                        ]
                    );
                }
            );
        }
    }

    private function getContentNode(int $contentNodeId): ?ContentNode
    {
        return $this->doctrine
            ->getRepository(ContentNode::class)
            ->findOneBy(['id' => $contentNodeId]);
    }

    /**
     * @param WebCatalog $webCatalog
     *
     * @return Scope[]
     */
    private function getUsedScopes(WebCatalog $webCatalog): array
    {
        /** @var WebCatalogRepository $repository */
        $repository = $this->doctrine->getRepository(WebCatalog::class);

        return $repository->getUsedScopes($webCatalog);
    }

    public static function getSubscribedTopics(): array
    {
        return [Topic::getName()];
    }
}
