<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\PdfDocument\Demand;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Demand\AbstractPdfDocumentDemand;

/**
 * Represents an abstract demand for generating a PDF document for an order.
 */
class AbstractOrderPdfDocumentDemand extends AbstractPdfDocumentDemand
{
    #[\Override]
    public function getSourceEntity(): Order
    {
        return parent::getSourceEntity();
    }

    /**
     * @return bool True if the PDF document name is set, false otherwise.
     */
    public function hasPdfDocumentName(): bool
    {
        return $this->pdfDocumentName !== null;
    }

    /**
     * Sets the name of the PDF document.
     *
     * @param string $pdfDocumentName The name of the PDF document (e.g., order-0101).
     *
     * @return self
     *
     * @throws \LogicException If the PDF document name is already set.
     */
    public function setPdfDocumentName(string $pdfDocumentName): self
    {
        if ($this->pdfDocumentName !== null) {
            throw new \LogicException('The PDF document name is already set.');
        }

        $this->pdfDocumentName = $pdfDocumentName;

        return $this;
    }
}
