<?php

namespace Oro\Bundle\ShoppingListBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Invalidate shopping list totals for shopping lists applicable for given context.
 */
class InvalidateTotalsByInventoryStatusPerProductProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ManagerRegistry $registry,
        MessageFactory $messageFactory,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->messageFactory = $messageFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_PRODUCT];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());
        $context = $this->messageFactory->getContext($data);
        if (!$context instanceof Website) {
            return self::ACK;
        }

        $products = $this->messageFactory->getProductIds($data);

        try {
            /** @var ShoppingListTotalRepository $repo */
            $repo = $this->registry
                ->getManagerForClass(ShoppingListTotal::class)
                ->getRepository(ShoppingListTotal::class);
            $repo->invalidateByProducts($context, $products);
        } catch (RetryableException $e) {
            $this->logger->error(
                'Retryable database exception occurred during shopping list totals invalidation',
                ['exception' => $e]
            );

            return self::REQUEUE;
        }

        return self::ACK;
    }
}
