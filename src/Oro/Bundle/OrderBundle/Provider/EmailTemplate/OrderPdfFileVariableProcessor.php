<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Provider\EmailTemplate;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\Manager\OrderPdfDocumentManagerInterface;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;

/**
 * Processes the order PDF file variable with configurable name (e.g. orderDefaultPdfFile) in email templates.
 * Creates or retrieves the PDF document for the order and sets it as the value of the variable.
 */
class OrderPdfFileVariableProcessor implements VariableProcessorInterface
{
    public function __construct(
        private readonly OrderPdfDocumentManagerInterface $orderPdfDocumentManager,
        private readonly ManagerRegistry $doctrine,
        private readonly string $pdfDocumentType
    ) {
    }

    #[\Override]
    public function process(string $variable, array $processorArguments, TemplateData $data): void
    {
        $order = $data->getEntityVariable($data->getParentVariablePath($variable));

        if (!$order instanceof Order) {
            $data->setComputedVariable($variable, null);

            return;
        }

        $pdfDocument = $this->orderPdfDocumentManager->retrievePdfDocument($order, $this->pdfDocumentType);

        $entityManager = $this->doctrine->getManagerForClass(PdfDocument::class);
        $entityManager->flush($pdfDocument);

        $data->setComputedVariable($variable, $pdfDocument?->getPdfDocumentFile());
    }
}
