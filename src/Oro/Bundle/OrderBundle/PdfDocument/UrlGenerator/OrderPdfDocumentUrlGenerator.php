<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\PdfDocument\UrlGenerator;

use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\Manager\OrderPdfDocumentManagerInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\UrlGenerator\PdfDocumentUrlGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Generates URLs for order PDF documents.
 */
class OrderPdfDocumentUrlGenerator implements OrderPdfDocumentUrlGeneratorInterface
{
    public function __construct(
        private readonly OrderPdfDocumentManagerInterface $orderPdfDocumentManager,
        private readonly PdfDocumentUrlGeneratorInterface $pdfDocumentUrlGenerator
    ) {
    }

    #[\Override]
    public function generateUrl(
        Order $order,
        string $pdfDocumentType,
        string $fileAction = FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): ?string {
        $pdfDocument = $this->orderPdfDocumentManager->retrievePdfDocument($order, $pdfDocumentType);
        if ($pdfDocument === null) {
            return null;
        }

        return $this->pdfDocumentUrlGenerator->generateUrl($pdfDocument, $fileAction, $referenceType);
    }
}
