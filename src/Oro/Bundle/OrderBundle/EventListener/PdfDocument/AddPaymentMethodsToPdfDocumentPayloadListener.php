<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\PdfDocument;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;

/**
 * Listener that adds order payment methods to the PDF document payload.
 */
final class AddPaymentMethodsToPdfDocumentPayloadListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PaymentTransactionProvider $paymentTransactionProvider,
    ) {
    }

    public function onBeforePdfDocumentGenerated(BeforePdfDocumentGeneratedEvent $event): void
    {
        $pdfDocument = $event->getPdfDocument();
        if ($pdfDocument->getSourceEntityClass() !== Order::class) {
            return;
        }

        $order = $this->doctrine->getManagerForClass(Order::class)
            ->find(Order::class, $pdfDocument->getSourceEntityId());

        if (!$order) {
            return;
        }

        $payload = $event->getPdfDocumentPayload();
        $payload['paymentMethods'] = $this->paymentTransactionProvider->getPaymentMethods($order);

        $event->setPdfDocumentPayload($payload);
    }
}
