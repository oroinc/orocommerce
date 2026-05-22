<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\Webhook;

use Oro\Bundle\IntegrationBundle\Event\WebhookTopicCollectEvent;
use Oro\Bundle\IntegrationBundle\Model\WebhookTopic;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Registers the "order.payment_status_updated" webhook topic.
 *
 * The topic is only added when Order entities have webhooks enabled (webhook_accessible: true).
 */
final class OrderPaymentStatusWebhookTopicListener
{
    public const string TOPIC = 'order.payment_status_updated';

    public function __construct(
        private readonly WebhookConfigurationProvider $webhookConfigurationProvider
    ) {
    }

    public function onWebhookTopicCollect(WebhookTopicCollectEvent $event): void
    {
        if (!$this->webhookConfigurationProvider->isEntityClassAccessibleByWebhooks(Order::class)) {
            return;
        }

        $event->addTopic(new WebhookTopic(
            self::TOPIC,
            'Order payment status updated',
            [
                WebhookConfigurationProvider::ENTITY_CLASS_KEY => Order::class,
                WebhookConfigurationProvider::ICON_KEY => $this->webhookConfigurationProvider->getIcon(Order::class)
            ]
        ));
    }
}
