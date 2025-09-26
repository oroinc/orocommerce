<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\PdfDocument\Generator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfDocumentSourceEntityNotFound;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Generator\PdfDocumentGeneratorInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * PDF document generator that generates a PDF file based on the provided PDF document.
 * Decorates the order PDF document generator to switch the website and localization
 * before and after the PDF generation.
 */
class OrderPdfDocumentGenerator implements PdfDocumentGeneratorInterface
{
    public function __construct(
        private readonly PdfDocumentGeneratorInterface $orderPdfDocumentGenerator,
        private readonly ManagerRegistry $doctrine,
        private readonly WebsiteManager $websiteManager,
        private readonly PreferredLocalizationProviderInterface $preferredLocalizationProvider,
        private readonly LocalizationProviderInterface $localizationProvider,
        private readonly string $pdfDocumentType
    ) {
    }

    #[\Override]
    public function isApplicable(AbstractPdfDocument $pdfDocument): bool
    {
        if (!$this->orderPdfDocumentGenerator->isApplicable($pdfDocument)) {
            return false;
        }

        return $pdfDocument->getSourceEntityClass() === Order::class &&
            $pdfDocument->getPdfDocumentType() === $this->pdfDocumentType;
    }

    /**
     * @throws PdfDocumentSourceEntityNotFound When the source entity is not found.
     */
    #[\Override]
    public function generatePdfFile(AbstractPdfDocument $pdfDocument): PdfFileInterface
    {
        $orderRepository = $this->doctrine->getRepository(Order::class);
        $order = $orderRepository->find((int)$pdfDocument->getSourceEntityId());
        if ($order === null) {
            throw PdfDocumentSourceEntityNotFound::factory(
                $pdfDocument->getSourceEntityClass(),
                $pdfDocument->getSourceEntityId()
            );
        }

        $orderWebsite = $order->getWebsite() ?? $this->websiteManager->getDefaultWebsite();
        $orderLocalization = $this->preferredLocalizationProvider->getPreferredLocalization($order);

        $originalWebsite = $this->websiteManager->getCurrentWebsite();
        $originalLocalization = $this->localizationProvider->getCurrentLocalization();

        try {
            $this->websiteManager->setCurrentWebsite($orderWebsite);
            $this->localizationProvider->setCurrentLocalization($orderLocalization);

            return $this->orderPdfDocumentGenerator->generatePdfFile($pdfDocument);
        } finally {
            $this->websiteManager->setCurrentWebsite($originalWebsite);
            $this->localizationProvider->setCurrentLocalization($originalLocalization);
        }
    }
}
