<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\OrderPdfDocumentType;
use Oro\Bundle\OrderBundle\PdfDocument\UrlGenerator\OrderPdfDocumentUrlGeneratorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides the TWIG filters to generate a download URL for an order PDF document.
 */
final class OrderPdfDocumentUrlExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'oro_order_pdf_document_back_office_url',
                $this->getPdfDocumentBackOfficeUrl(...)
            ),
            new TwigFilter(
                'oro_order_pdf_document_order_default_back_office_url',
                $this->getOrderDefaultPdfDocumentBackOfficeUrl(...)
            ),
            new TwigFilter(
                'oro_order_pdf_document_storefront_url',
                $this->getPdfDocumentStorefrontUrl(...)
            ),
            new TwigFilter(
                'oro_order_pdf_document_order_default_storefront_url',
                $this->getOrderDefaultPdfDocumentStorefrontUrl(...)
            ),
        ];
    }

    /**
     * Generates a download back-office URL for the specified order PDF document.
     *
     * @param Order $order The order for which the URL of PDF document is generated.
     * @param string $pdfDocumentType The type of the PDF document (e.g., us_standard_order).
     *
     * @return string|null The generated URL or null if the PDF document does not exist.
     */
    public function getPdfDocumentBackOfficeUrl(Order $order, string $pdfDocumentType): ?string
    {
        return $this->getBackendOrderPdfDocumentUrlGenerator()->generateUrl($order, $pdfDocumentType);
    }

    /**
     * Generates a download back-office URL for the default order PDF document.
     *
     * @param Order $order The order for which the URL of PDF document is generated.
     *
     * @return string|null The generated URL or null if the PDF document does not exist.
     */
    public function getOrderDefaultPdfDocumentBackOfficeUrl(Order $order): ?string
    {
        return $this->getPdfDocumentBackOfficeUrl($order, OrderPdfDocumentType::DEFAULT);
    }

    /**
     * Generates a download storefront URL for the specified order PDF document.
     *
     * @param Order $order The order for which the URL of PDF document is generated.
     * @param string $pdfDocumentType The type of the PDF document (e.g., us_standard_order).
     *
     * @return string|null The generated URL or null if the PDF document does not exist.
     */
    public function getPdfDocumentStorefrontUrl(Order $order, string $pdfDocumentType): ?string
    {
        return $this->getFrontendOrderPdfDocumentUrlGenerator()->generateUrl($order, $pdfDocumentType);
    }

    /**
     * Generates a download storefront URL for the default order PDF document.
     *
     * @param Order $order The order for which the URL of PDF document is generated.
     *
     * @return string|null The generated URL or null if the PDF document does not exist.
     */
    public function getOrderDefaultPdfDocumentStorefrontUrl(Order $order): ?string
    {
        return $this->getPdfDocumentStorefrontUrl($order, OrderPdfDocumentType::DEFAULT);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_order.pdf_document.url_generator.back_office' => OrderPdfDocumentUrlGeneratorInterface::class,
            'oro_order.pdf_document.url_generator.storefront' => OrderPdfDocumentUrlGeneratorInterface::class,
        ];
    }

    private function getBackendOrderPdfDocumentUrlGenerator(): OrderPdfDocumentUrlGeneratorInterface
    {
        return $this->container->get('oro_order.pdf_document.url_generator.back_office');
    }

    private function getFrontendOrderPdfDocumentUrlGenerator(): OrderPdfDocumentUrlGeneratorInterface
    {
        return $this->container->get('oro_order.pdf_document.url_generator.storefront');
    }
}
