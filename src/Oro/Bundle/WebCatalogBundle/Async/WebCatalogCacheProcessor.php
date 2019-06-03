<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ScopeMatcher;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException as MessageQueueInvalidArgumentException;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException as OptionsResolverInvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
     * @var ConfigManager
     */
    private $configManager;

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
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if (!$message->getBody()) {
            $this->logger->error('Message body is empty.');

            return self::REJECT;
        }

        $messageData = $this->getMessageData($message);
        $webCatalogId = $messageData['webCatalogId'];

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            sprintf('%s:%s', Topics::CALCULATE_WEB_CATALOG_CACHE, $webCatalogId),
            function () use ($webCatalogId) {
                $webCatalog = $this->getWebCatalog($webCatalogId);
                $nodes = $this->getRootNodesByWebCatalog($webCatalog);
                foreach ($nodes as $node) {
                    $this->producer->send(Topics::CALCULATE_CONTENT_NODE_CACHE, ['contentNodeId' => $node->getId()]);
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
    private function getRootNodesByWebCatalog(WebCatalog $webCatalog)
    {
        $websites = $this->getWebsites();
        $webCatalogValues = $this->configManager->getValues('oro_web_catalog.web_catalog', $websites);
        $navigationRootValues = [];
        foreach ($webCatalogValues as $websiteId => $value) {
            if ((int) $value !== $webCatalog->getId()) {
                continue;
            }

            $navigationRootValue = $this->configManager
                ->get('oro_web_catalog.navigation_root', false, false, $websites[$websiteId]);
            $contentNode = $this->getContentNode($webCatalog, $navigationRootValue);
            if (!$contentNode) {
                continue;
            }

            $navigationRootValues[] = $contentNode->getId();
        }

        $contentNodeRepo = $this->registry
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);

        return $contentNodeRepo->findBy(['id' => array_unique($navigationRootValues)]);
    }

    /**
     * @return array
     */
    private function getWebsites(): array
    {
        $repository = $this->registry
            ->getManagerForClass(Website::class)
            ->getRepository(Website::class);

        return $repository->getAllWebsites();
    }

    /**
     * @param WebCatalog $webCatalog
     * @param int $contentNodeId
     *
     * @return ContentNode
     */
    private function getContentNode(WebCatalog $webCatalog, $contentNodeId)
    {
        $repository = $this->registry
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);

        $contentNode = $repository->findOneBy(['id' => $contentNodeId]);
        if (!$contentNode) {
            $contentNode = $repository->findOneBy(['webCatalog' => $webCatalog, 'parentNode' => null]);
        }

        return $contentNode;
    }

    /**
     * @param int $webCatalogId
     *
     * @return WebCatalog
     */
    private function getWebCatalog($webCatalogId): WebCatalog
    {
        $repository = $this->registry
            ->getManagerForClass(WebCatalog::class)
            ->getRepository(WebCatalog::class);

        return $repository->findOneBy(['id' => $webCatalogId]);
    }

    /**
     * @param MessageInterface $message
     *
     * @return array
     */
    private function getMessageData(MessageInterface $message): array
    {
        $body = JSON::decode($message->getBody());

        // backward compatibility, up to version 3.1.x message body contains scalar value with web catalog id
        if (is_scalar($body)) {
            $body = ['webCatalogId' => (int) $message->getBody()];
        }

        try {
            return $this->getOptionsResolver()->resolve((array)$body);
        } catch (OptionsResolverInvalidArgumentException $e) {
            throw new MessageQueueInvalidArgumentException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['webCatalogId']);
        $resolver->setAllowedTypes('webCatalogId', 'int');

        return $resolver;
    }

    /**
     * @param $webCatalogId
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
     * @return array
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
