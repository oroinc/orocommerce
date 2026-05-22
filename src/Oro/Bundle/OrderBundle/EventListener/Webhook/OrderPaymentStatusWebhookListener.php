<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\Webhook;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\EventListener\AbstractPaymentStatusListener;

/**
 * Sends a webhook notification when an Order payment status is updated.
 *
 * The notification payload includes the entity type and ID, the new payment status
 * (code and label), the net amount paid, the remaining amount due, and the order currency.
 */
final class OrderPaymentStatusWebhookListener extends AbstractPaymentStatusListener
{
    protected function processTransactionComplete(PaymentTransaction $transaction, object $object): bool
    {
        if (!$object instanceof Order) {
            return false;
        }

        $this->paymentStatusWebhookNotifier->notify(
            OrderPaymentStatusWebhookTopicListener::TOPIC,
            $transaction,
            (float)($object->getTotal() ?? 0.0)
        );

        return true;
    }
}
