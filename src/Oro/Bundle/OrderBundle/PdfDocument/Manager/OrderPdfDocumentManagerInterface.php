<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\PdfDocument\Manager;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\Demand\AbstractOrderPdfDocumentDemand;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;

/**
 * Interface for order PDF manager.
 */
interface OrderPdfDocumentManagerInterface
{
    /**
     * Checks if a PDF document of the specified type exists for the given order.
     *
     * @param Order $order The order entity to check the PDF document for.
     * @param string $pdfDocumentType The type of the PDF document (e.g., us_standard_order).
     *
     * @return bool True if the PDF document exists, false otherwise.
     */
    public function hasPdfDocument(Order $order, string $pdfDocumentType): bool;

    /**
     * Retrieves a PDF document of the specified type for the given order.
     *
     * @param Order $order The order entity to retrieve the PDF document for.
     * @param string $pdfDocumentType The type of the PDF document (e.g., us_standard_order).
     *
     * @return AbstractPdfDocument|null The PDF document if it exists, null otherwise.
     */
    public function retrievePdfDocument(Order $order, string $pdfDocumentType): ?AbstractPdfDocument;

    /**
     * Creates a new PDF document for the given order based on the provided demand.
     *
     * @param AbstractOrderPdfDocumentDemand $orderPdfDocumentDemand The demand for generating the PDF document.
     *
     * @return AbstractPdfDocument The created PDF document.
     */
    public function createPdfDocument(AbstractOrderPdfDocumentDemand $orderPdfDocumentDemand): AbstractPdfDocument;

    /**
     * Updates an existing PDF document associated with the given order.
     *
     * @param Order $order The order entity to update the PDF document for.
     * @param string $pdfDocumentType The type of the PDF document (e.g., us_standard_order).
     *
     * @return AbstractPdfDocument|null The updated PDF document if it exists, null otherwise.
     */
    public function updatePdfDocument(Order $order, string $pdfDocumentType): ?AbstractPdfDocument;

    /**
     * Deletes a PDF document of the specified type for the given order.
     *
     * @param Order $order The order entity to delete the PDF document for.
     * @param string $pdfDocumentType The type of the PDF document (e.g., us_standard_order).
     *
     * @return AbstractPdfDocument|null The deleted PDF document if it exists, null otherwise.
     */
    public function deletePdfDocument(Order $order, string $pdfDocumentType): ?AbstractPdfDocument;
}
