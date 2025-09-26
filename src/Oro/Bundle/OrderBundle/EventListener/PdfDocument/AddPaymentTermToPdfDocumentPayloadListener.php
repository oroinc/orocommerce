<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\PdfDocument;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;

/**
 * Listener that adds order payment term to the PDF document payload.
 */
final class AddPaymentTermToPdfDocumentPayloadListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PaymentTermAssociationProvider $paymentTermAssociationProvider,
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

        $associationNames = $this->paymentTermAssociationProvider->getAssociationNames(Order::class);
        foreach ($associationNames as $associationName) {
            $paymentTerm = $this->paymentTermAssociationProvider->getPaymentTerm($order, $associationName);
            if ($paymentTerm) {
                $payload['paymentTerm'] = $paymentTerm;
                $event->setPdfDocumentPayload($payload);

                break;
            }
        }
    }
}
