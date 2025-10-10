<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\PdfDocument\Manager;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\Demand\AbstractOrderPdfDocumentDemand;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Name\PdfDocumentNameProviderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator\PdfDocumentOperatorInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator\PdfDocumentOperatorRegistry;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Provider\SinglePdfDocumentBySourceEntityProviderInterface;

/**
 * Manages PDF documents associated with orders.
 */
class OrderPdfDocumentManager implements OrderPdfDocumentManagerInterface
{
    public function __construct(
        private readonly SinglePdfDocumentBySourceEntityProviderInterface $singlePdfDocumentBySourceEntityProvider,
        private readonly PdfDocumentOperatorRegistry $pdfDocumentOperatorRegistry,
        private readonly PdfDocumentNameProviderInterface $pdfDocumentNameProvider,
        private readonly string $pdfDocumentGenerationMode
    ) {
    }

    #[\Override]
    public function hasPdfDocument(Order $order, string $pdfDocumentType): bool
    {
        return $this->findPdfDocument($order, $pdfDocumentType) !== null;
    }

    #[\Override]
    public function retrievePdfDocument(Order $order, string $pdfDocumentType): ?AbstractPdfDocument
    {
        $pdfDocument = $this->findPdfDocument($order, $pdfDocumentType);
        if ($pdfDocument === null) {
            return null;
        }

        $this->getPdfDocumentOperator()->resolvePdfDocument($pdfDocument);

        return $pdfDocument;
    }

    #[\Override]
    public function createPdfDocument(AbstractOrderPdfDocumentDemand $orderPdfDocumentDemand): AbstractPdfDocument
    {
        $order = $orderPdfDocumentDemand->getSourceEntity();

        if (!$orderPdfDocumentDemand->hasPdfDocumentName()) {
            $pdfDocumentName = $this->pdfDocumentNameProvider
                ->createPdfDocumentName($order);
            $orderPdfDocumentDemand->setPdfDocumentName($pdfDocumentName);
        }

        $pdfDocument = $this->getPdfDocumentOperator()->createPdfDocument($orderPdfDocumentDemand);
        $order->addPdfDocument($pdfDocument);

        return $pdfDocument;
    }

    #[\Override]
    public function updatePdfDocument(Order $order, string $pdfDocumentType): ?AbstractPdfDocument
    {
        $pdfDocument = $this->findPdfDocument($order, $pdfDocumentType);
        if ($pdfDocument === null) {
            return null;
        }

        $this->getPdfDocumentOperator()->updatePdfDocument($pdfDocument);

        return $pdfDocument;
    }

    #[\Override]
    public function deletePdfDocument(Order $order, string $pdfDocumentType): ?AbstractPdfDocument
    {
        $pdfDocument = $this->findPdfDocument($order, $pdfDocumentType);
        if ($pdfDocument === null) {
            return null;
        }

        $order->removePdfDocument($pdfDocument);
        $this->getPdfDocumentOperator()->deletePdfDocument($pdfDocument);

        return $pdfDocument;
    }

    private function getPdfDocumentOperator(): PdfDocumentOperatorInterface
    {
        return $this->pdfDocumentOperatorRegistry->getOperator(
            Order::class,
            $this->pdfDocumentGenerationMode
        );
    }

    private function findPdfDocument(Order $order, string $pdfDocumentType): ?AbstractPdfDocument
    {
        $pdfDocumentName = $this->pdfDocumentNameProvider->createPdfDocumentName($order);

        return $this->singlePdfDocumentBySourceEntityProvider
            ->findPdfDocument($order, $pdfDocumentName, $pdfDocumentType);
    }
}
