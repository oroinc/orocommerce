<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\PdfDocument;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;

/**
 * Listener that adds order payment status to the PDF document payload.
 */
final class AddPaymentStatusToPdfDocumentPayloadListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PaymentStatusManager $paymentStatusManager,
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
        $payload['paymentStatus'] = (string)$this->paymentStatusManager->getPaymentStatus($order);

        $event->setPdfDocumentPayload($payload);
    }
}
