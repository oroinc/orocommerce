<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ShoppingListBundle\Async\MessageFactory;
use Oro\Bundle\ShoppingListBundle\Async\Topic\InvalidateTotalsByInventoryStatusPerWebsiteTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Listen for oro_product.general_frontend_product_visibility changes.
 * Schedule totals invalidation for Shopping List containing products with unavailable inventory statuses.
 */
class ConfigurationListener
{
    private const CONFIG_PATH = 'oro_product.general_frontend_product_visibility';

    private MessageProducerInterface $producer;

    private MessageFactory $messageFactory;

    public function __construct(
        MessageProducerInterface $producer,
        MessageFactory $messageFactory
    ) {
        $this->producer = $producer;
        $this->messageFactory = $messageFactory;
    }

    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(self::CONFIG_PATH)) {
            $diff = array_diff(
                (array)$event->getOldValue(self::CONFIG_PATH),
                (array)$event->getNewValue(self::CONFIG_PATH)
            );

            // Schedule totals invalidation only on decreasing of allowed list
            if ($diff) {
                $this->producer->send(
                    InvalidateTotalsByInventoryStatusPerWebsiteTopic::getName(),
                    $this->messageFactory->createShoppingListTotalsInvalidateMessageForConfigScope(
                        $event->getScope(),
                        $event->getScopeId()
                    )
                );
            }
        }
    }
}
