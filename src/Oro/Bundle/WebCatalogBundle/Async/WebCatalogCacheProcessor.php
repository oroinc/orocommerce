<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic as Topic;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Initiates web catalog cache calculation.
 */
class WebCatalogCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private JobRunner $jobRunner;

    private MessageProducerInterface $producer;

    private ManagerRegistry $registry;

    private ConfigManager $configManager;

    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ManagerRegistry $registry,
        ConfigManager $configManager,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $message->getBody();
        $jobName = sprintf('%s:%s', Topic::getName(), $messageBody[Topic::WEB_CATALOG_ID]);

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobName,
            function () use ($messageBody) {
                $webCatalog = $this->getWebCatalog($messageBody[Topic::WEB_CATALOG_ID]);
                if (!$webCatalog) {
                    $this->logger->error('Web catalog #{webCatalogId} is not found', $messageBody);
                    return false;
                }

                $nodes = $this->getRootNodesByWebCatalog($webCatalog);
                foreach ($nodes as $node) {
                    $this->producer->send(
                        WebCatalogCalculateContentNodeCacheTopic::getName(),
                        [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => $node->getId()]
                    );
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param WebCatalog $webCatalog
     *
     * @return ContentNode[]
     */
    private function getRootNodesByWebCatalog(WebCatalog $webCatalog): array
    {
        $websites = $this->getWebsites($webCatalog);
        $webCatalogValues = $this->configManager->getValues('oro_web_catalog.web_catalog', $websites);
        $navigationRootValues = [];
        foreach ($webCatalogValues as $websiteId => $value) {
            if ((int)$value !== $webCatalog->getId()) {
                continue;
            }

            // CE application has scope resolved website ID equal to 0
            $website = $websiteId ? $websites[$websiteId] : null;
            $navigationRootValue = $this->configManager
                ->get('oro_web_catalog.navigation_root', false, false, $website);
            $contentNode = $this->getContentNode($webCatalog, $navigationRootValue);
            if (!$contentNode) {
                continue;
            }

            $navigationRootValues[] = $contentNode->getId();
        }

        return $this->registry
            ->getRepository(ContentNode::class)
            ->findBy(['id' => array_unique($navigationRootValues)]);
    }

    private function getWebsites(WebCatalog $webCatalog): array
    {
        return $this->registry
            ->getRepository(Website::class)
            ->getAllWebsites($webCatalog->getOrganization());
    }

    private function getContentNode(WebCatalog $webCatalog, ?int $contentNodeId): ?ContentNode
    {
        $repository = $this->registry->getRepository(ContentNode::class);

        $contentNode = $repository->findOneBy(['id' => $contentNodeId]);
        if (!$contentNode) {
            $contentNode = $repository->findOneBy(['webCatalog' => $webCatalog, 'parentNode' => null]);
        }

        return $contentNode;
    }

    private function getWebCatalog(int $webCatalogId): ?WebCatalog
    {
        return $this->registry
            ->getRepository(WebCatalog::class)
            ->findOneBy(['id' => $webCatalogId]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topic::getName()];
    }
}
