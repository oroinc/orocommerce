<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\PdfDocument\UrlGenerator;

use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Interface for generating URLs for order PDF documents.
 */
interface OrderPdfDocumentUrlGeneratorInterface
{
    /**
     * Generates a URL for the given order PDF document.
     *
     * @param Order $order The order for which the URL of PDF document is generated.
     * @param string $pdfDocumentType The type of the PDF document (e.g., us_standard_order).
     * @param string $fileAction The action to perform on file. Expects constants from {@see FileUrlProviderInterface}.
     * @param int $referenceType The type of URL to generate. Expects constants from {@see UrlGeneratorInterface}.
     *
     * @return string|null The generated URL or null if the PDF document does not exist.
     */
    public function generateUrl(
        Order $order,
        string $pdfDocumentType,
        string $fileAction = FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): ?string;
}
