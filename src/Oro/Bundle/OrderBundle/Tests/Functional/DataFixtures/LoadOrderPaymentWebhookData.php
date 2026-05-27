<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\OrderBundle\EventListener\Webhook\OrderPaymentStatusWebhookTopicListener;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Persists an active WebhookProducerSettings record for the "order.payment_status_updated" topic.
 * Required by functional tests that verify webhook notification dispatch.
 */
class LoadOrderPaymentWebhookData extends AbstractFixture implements DependentFixtureInterface
{
    public const string WEBHOOK_PRODUCER_SETTINGS = 'order_payment_webhook_producer_settings';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUser::class,
            LoadOrganization::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $webhook = new WebhookProducerSettings();
        $webhook->setNotificationUrl('https://example.test/webhooks/order-payment-status');
        $webhook->setTopic(OrderPaymentStatusWebhookTopicListener::TOPIC);
        $webhook->setEnabled(true);
        $webhook->setVerifySsl(false);
        $webhook->setFormat('default');
        $webhook->setOwner($user);
        $webhook->setOrganization($user->getOrganization());

        $manager->persist($webhook);
        $manager->flush();

        $this->addReference(self::WEBHOOK_PRODUCER_SETTINGS, $webhook);
    }
}
