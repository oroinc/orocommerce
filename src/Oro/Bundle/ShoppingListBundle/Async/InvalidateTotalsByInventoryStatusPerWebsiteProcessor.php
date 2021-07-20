<?php

namespace Oro\Bundle\ShoppingListBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Schedule shopping list line items totals invalidation containing products with invisible inventory statuses.
 * Process only shopping for websites with changed settings.
 */
class InvalidateTotalsByInventoryStatusPerWebsiteProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var WebsiteProviderInterface
     */
    private $websiteProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ConfigManager $configManager,
        WebsiteProviderInterface $websiteProvider,
        ManagerRegistry $registry,
        MessageFactory $messageFactory,
        LoggerInterface $logger
    ) {
        $this->configManager = $configManager;
        $this->websiteProvider = $websiteProvider;
        $this->registry = $registry;
        $this->messageFactory = $messageFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_WEBSITE];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());
        $context = $this->messageFactory->getContext($data);
        $websitesToProcess = $this->getWebsitesToProcess($context);
        if (!$websitesToProcess) {
            // Nothing to do
            return self::ACK;
        }

        try {
            /** @var ShoppingListTotalRepository $repo */
            $repo = $this->registry
                ->getManagerForClass(ShoppingListTotal::class)
                ->getRepository(ShoppingListTotal::class);

            foreach ($websitesToProcess as $website) {
                $repo->invalidateByWebsite($website);
            }
        } catch (RetryableException $e) {
            $this->logger->error(
                'Retryable database exception occurred during shopping list totals invalidation',
                ['exception' => $e]
            );

            return self::REQUEUE;
        }

        return self::ACK;
    }

    /**
     * @param object|null $context
     * @return array
     */
    private function getWebsitesToProcess($context): array
    {
        if ($context) {
            return [$context];
        }

        $websites = $this->websiteProvider->getWebsites();
        $allowedStatusesPerWebsite = $this->configManager->getValues(
            'oro_product.general_frontend_product_visibility',
            $websites,
            false,
            true
        );

        $toProcess = [];
        foreach ($allowedStatusesPerWebsite as $websiteId => $config) {
            // Process only websites with fallback to parent scope
            if (!empty($config[ConfigManager::USE_PARENT_SCOPE_VALUE_KEY])) {
                $toProcess[] = $websites[$websiteId];
            }
        }

        return $toProcess;
    }
}
