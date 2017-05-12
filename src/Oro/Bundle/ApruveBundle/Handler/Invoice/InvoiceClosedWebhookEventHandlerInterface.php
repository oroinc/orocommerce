<?php

namespace Oro\Bundle\ApruveBundle\Handler\Invoice;

use Oro\Bundle\ApruveBundle\Handler\Exceptions\InvalidEventException;
use Oro\Bundle\ApruveBundle\Handler\Exceptions\SourceTransactionNotFoundException;
use Oro\Bundle\ApruveBundle\Handler\Exceptions\TransactionAlreadyExistsException;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

interface InvoiceClosedWebhookEventHandlerInterface
{
    /**
     * @param PaymentMethodInterface $paymentMethod
     * @param array                  $eventBody
     *
     * @throws InvalidEventException
     * @throws SourceTransactionNotFoundException
     * @throws TransactionAlreadyExistsException
     *
     * @return void
     */
    public function handle(PaymentMethodInterface $paymentMethod, array $eventBody);
}
