<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\PdfDocument;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * Listener that adds order totals information to the PDF document payload.
 */
final class AddTotalsToPdfDocumentPayloadListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly TotalProcessorProvider $totalProcessorProvider,
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
        $payload['totals'] = $this->totalProcessorProvider->getTotalWithSubtotalsAsArray($order);

        $event->setPdfDocumentPayload($payload);
    }
}
