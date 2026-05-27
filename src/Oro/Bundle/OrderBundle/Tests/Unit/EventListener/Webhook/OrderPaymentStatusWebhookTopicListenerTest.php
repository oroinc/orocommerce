<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Webhook;

use Oro\Bundle\IntegrationBundle\Event\WebhookTopicCollectEvent;
use Oro\Bundle\IntegrationBundle\Model\WebhookTopic;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\Webhook\OrderPaymentStatusWebhookTopicListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderPaymentStatusWebhookTopicListenerTest extends TestCase
{
    private WebhookConfigurationProvider&MockObject $webhookConfigurationProvider;
    private OrderPaymentStatusWebhookTopicListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->webhookConfigurationProvider = $this->createMock(WebhookConfigurationProvider::class);
        $this->listener = new OrderPaymentStatusWebhookTopicListener($this->webhookConfigurationProvider);
    }

    public function testOnWebhookTopicCollectSkipsWhenEntityNotAccessible(): void
    {
        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityClassAccessibleByWebhooks')
            ->with(Order::class)
            ->willReturn(false);

        $this->webhookConfigurationProvider->expects(self::never())
            ->method('getIcon');

        $event = new WebhookTopicCollectEvent([]);
        $this->listener->onWebhookTopicCollect($event);

        self::assertEmpty($event->getTopics());
    }

    public function testOnWebhookTopicCollectAddsTopic(): void
    {
        $icon = 'fa-shopping-cart';

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityClassAccessibleByWebhooks')
            ->with(Order::class)
            ->willReturn(true);

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getIcon')
            ->with(Order::class)
            ->willReturn($icon);

        $event = new WebhookTopicCollectEvent([]);
        $this->listener->onWebhookTopicCollect($event);

        $topics = $event->getTopics();
        self::assertCount(1, $topics);
        self::assertArrayHasKey(OrderPaymentStatusWebhookTopicListener::TOPIC, $topics);

        $topic = $topics[OrderPaymentStatusWebhookTopicListener::TOPIC];
        self::assertInstanceOf(WebhookTopic::class, $topic);
        self::assertSame(OrderPaymentStatusWebhookTopicListener::TOPIC, $topic->getName());
        self::assertSame('Order payment status updated', $topic->getLabel());
        self::assertEquals(
            [
                WebhookConfigurationProvider::ENTITY_CLASS_KEY => Order::class,
                WebhookConfigurationProvider::ICON_KEY => $icon,
            ],
            $topic->getMetadata()
        );
    }

    public function testTopicConstantValue(): void
    {
        self::assertSame('order.payment_status_updated', OrderPaymentStatusWebhookTopicListener::TOPIC);
    }
}
