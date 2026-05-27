<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Webhook;

use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotifierInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentInfoProviderInterface;

/**
 * Builds and sends a webhook notification payload for a payment status change event.
 *
 * The payload follows the JSON:API structure and contains:
 * - the entity type and ID,
 * - the new payment status code and its human-readable label,
 * - the net amount paid, the remaining amount due, and the entity currency.
 */
class PaymentStatusWebhookNotifier
{
    public function __construct(
        private readonly WebhookNotifierInterface $webhookNotifier,
        private readonly PaymentInfoProviderInterface $paymentInfoProvider,
        private readonly PaymentStatusLabelFormatter $paymentStatusLabelFormatter,
        private readonly EntityAliasResolverRegistry $entityAliasResolverRegistry,
    ) {
    }

    /**
     * Sends a webhook notification for a payment status update.
     *
     * The entity class, entity ID, and payment status code are extracted from the event.
     * The JSON:API resource type is resolved via the entity plural alias.
     */
    public function notify(
        string $topic,
        PaymentTransaction $paymentTransaction,
        float $totalAmount
    ): void {
        $entityClass = $paymentTransaction->getEntityClass();
        $entityId = $paymentTransaction->getEntityIdentifier();
        $paymentStatusName = $this->paymentInfoProvider
            ->getPaymentStatus($entityClass, $entityId)
            ->getPaymentStatus() ?? '';
        $entityApiType = $this->entityAliasResolverRegistry
            ->getEntityAliasResolver(new RequestType([RequestType::REST, RequestType::JSON_API]))
            ->getPluralAlias($entityClass);

        $amountPaid = $this->paymentInfoProvider->getAmountPaid($entityClass, $entityId);
        $amountDue = $this->paymentInfoProvider->getAmountDue($entityClass, $entityId, $totalAmount);
        $eventData = [
            'data' => [
                'type' => $entityApiType,
                'id' => $entityId,
                'attributes' => [
                    'paymentStatus' => $paymentStatusName,
                    'paymentStatusLabel' => $this->paymentStatusLabelFormatter
                        ->formatPaymentStatusLabel($paymentStatusName),
                    'transactionAmount' => (float)$paymentTransaction->getAmount(),
                    'transactionType' => $paymentTransaction->getAction(),
                    'transactionDate' => $paymentTransaction->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'amountPaid' => $amountPaid,
                    'amountDue' => $amountDue,
                    'currency' => $paymentTransaction->getCurrency()
                ],
            ],
        ];

        $this->webhookNotifier->sendNotification($topic, $eventData);
        $this->webhookNotifier->sendNotification($topic . '.' . $entityId, $eventData);
    }
}
