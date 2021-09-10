<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ShoppingListBundle\Async\MessageFactory;
use Oro\Bundle\ShoppingListBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Listen for oro_product.general_frontend_product_visibility changes.
 * Schedule totals invalidation for Shopping List containing products with unavailable inventory statuses.
 */
class ConfigurationListener
{
    private const CONFIG_PATH = 'oro_product.general_frontend_product_visibility';

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

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
                    Topics::INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_WEBSITE,
                    $this->messageFactory->createShoppingListTotalsInvalidateMessageForConfigScope(
                        $event->getScope(),
                        $event->getScopeId()
                    )
                );
            }
        }
    }
}
