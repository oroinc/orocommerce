<?php

namespace Oro\Bundle\ShoppingListBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShoppingListBundle\Async\Topic\InvalidateTotalsByInventoryStatusPerProductTopic;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Invalidate shopping list totals for shopping lists applicable for given context.
 */
class InvalidateTotalsByInventoryStatusPerProductProcessor implements
    LoggerAwareInterface,
    MessageProcessorInterface,
    TopicSubscriberInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $registry;

    private MessageFactory $messageFactory;

    public function __construct(
        ManagerRegistry $registry,
        MessageFactory $messageFactory
    ) {
        $this->registry = $registry;
        $this->messageFactory = $messageFactory;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [InvalidateTotalsByInventoryStatusPerProductTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();
        $context = $this->messageFactory->getContext($data);
        if (!$context instanceof Website) {
            return self::ACK;
        }

        $products = $this->messageFactory->getProductIds($data);

        try {
            /** @var ShoppingListTotalRepository $repo */
            $repo = $this->registry
                ->getManagerForClass(ShoppingListTotal::class)
                ?->getRepository(ShoppingListTotal::class);
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
