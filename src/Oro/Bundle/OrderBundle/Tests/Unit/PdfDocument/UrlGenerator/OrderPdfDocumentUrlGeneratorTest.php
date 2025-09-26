<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\PdfDocument\UrlGenerator;

use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\Manager\OrderPdfDocumentManagerInterface;
use Oro\Bundle\OrderBundle\PdfDocument\OrderPdfDocumentType;
use Oro\Bundle\OrderBundle\PdfDocument\UrlGenerator\OrderPdfDocumentUrlGenerator;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\UrlGenerator\PdfDocumentUrlGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrderPdfDocumentUrlGeneratorTest extends TestCase
{
    private OrderPdfDocumentManagerInterface&MockObject $orderPdfDocumentManager;
    private PdfDocumentUrlGeneratorInterface&MockObject $pdfDocumentUrlGenerator;
    private OrderPdfDocumentUrlGenerator $urlGenerator;

    protected function setUp(): void
    {
        $this->orderPdfDocumentManager = $this->createMock(OrderPdfDocumentManagerInterface::class);
        $this->pdfDocumentUrlGenerator = $this->createMock(PdfDocumentUrlGeneratorInterface::class);

        $this->urlGenerator = new OrderPdfDocumentUrlGenerator(
            $this->orderPdfDocumentManager,
            $this->pdfDocumentUrlGenerator
        );
    }

    public function testGenerateUrlReturnsNullWhenPdfDocumentDoesNotExist(): void
    {
        $order = new Order();
        $pdfDocumentType = 'us_standard_order';

        $this->orderPdfDocumentManager
            ->expects(self::once())
            ->method('retrievePdfDocument')
            ->with($order, $pdfDocumentType)
            ->willReturn(null);

        $this->pdfDocumentUrlGenerator
            ->expects(self::never())
            ->method('generateUrl');

        $result = $this->urlGenerator->generateUrl($order, $pdfDocumentType);

        self::assertNull($result);
    }

    public function testGenerateUrlReturnsUrlWhenPdfDocumentExists(): void
    {
        $order = new Order();
        $expectedUrl = '/path/to/pdf/document';
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);

        $this->orderPdfDocumentManager
            ->expects(self::once())
            ->method('retrievePdfDocument')
            ->with($order, OrderPdfDocumentType::DEFAULT)
            ->willReturn($pdfDocument);

        $this->pdfDocumentUrlGenerator
            ->expects(self::once())
            ->method('generateUrl')
            ->with(
                $pdfDocument,
                FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn($expectedUrl);

        $result = $this->urlGenerator->generateUrl($order, OrderPdfDocumentType::DEFAULT);

        self::assertEquals($expectedUrl, $result);
    }

    public function testGenerateUrlWithCustomFileActionAndReferenceType(): void
    {
        $order = new Order();
        $fileAction = FileUrlProviderInterface::FILE_ACTION_GET;
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL;
        $expectedUrl = 'https://example.com/path/to/pdf/document';
        $pdfDocument = $this->createMock(AbstractPdfDocument::class);

        $this->orderPdfDocumentManager
            ->expects(self::once())
            ->method('retrievePdfDocument')
            ->with($order, OrderPdfDocumentType::DEFAULT)
            ->willReturn($pdfDocument);

        $this->pdfDocumentUrlGenerator
            ->expects(self::once())
            ->method('generateUrl')
            ->with($pdfDocument, $fileAction, $referenceType)
            ->willReturn($expectedUrl);

        $result = $this->urlGenerator->generateUrl($order, OrderPdfDocumentType::DEFAULT, $fileAction, $referenceType);

        self::assertEquals($expectedUrl, $result);
    }
}
