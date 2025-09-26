<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\PdfDocument\Demand;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\OrderPdfDocumentType;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;

/**
 * Represents a generic demand for generating a PDF document for an order.
 */
class GenericOrderPdfDocumentDemand extends AbstractOrderPdfDocumentDemand
{
    /**
     * @param Order $sourceEntity The order for which to generate the PDF document.
     * @param string $pdfDocumentType The type of the PDF document (e.g., us_standard_order).
     * @param string $pdfOptionsPreset The PDF options preset name (e.g., default, letter, a4, etc.).
     * @param array $pdfDocumentPayload The arbitrary payload data to be passed to the PDF generator.
     */
    public function __construct(
        Order $sourceEntity,
        string $pdfDocumentType = OrderPdfDocumentType::DEFAULT,
        string $pdfOptionsPreset = PdfOptionsPreset::DEFAULT,
        array $pdfDocumentPayload = []
    ) {
        $this->sourceEntity = $sourceEntity;
        // Expected to be set in the setPdfDocumentType method.
        $this->pdfDocumentName = null;
        $this->pdfDocumentType = $pdfDocumentType;
        $this->pdfOptionsPreset = $pdfOptionsPreset;
        $this->pdfDocumentPayload = $pdfDocumentPayload;
    }
}
