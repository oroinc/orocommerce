<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\OrderPdfDocumentType;
use Oro\Bundle\OrderBundle\PdfDocument\UrlGenerator\OrderPdfDocumentUrlGeneratorInterface;
use Oro\Bundle\OrderBundle\Twig\OrderPdfDocumentUrlExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderPdfDocumentUrlExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private OrderPdfDocumentUrlExtension $extension;

    private MockObject&OrderPdfDocumentUrlGeneratorInterface $orderPdfDocumentBackOfficeUrlGenerator;

    private MockObject&OrderPdfDocumentUrlGeneratorInterface $orderPdfDocumentStorefrontUrlGenerator;

    protected function setUp(): void
    {
        $this->orderPdfDocumentBackOfficeUrlGenerator = $this->createMock(OrderPdfDocumentUrlGeneratorInterface::class);
        $this->orderPdfDocumentStorefrontUrlGenerator = $this->createMock(OrderPdfDocumentUrlGeneratorInterface::class);

        $this->extension = new OrderPdfDocumentUrlExtension(
            self::getContainerBuilder()
                ->add(
                    'oro_order.pdf_document.url_generator.back_office',
                    $this->orderPdfDocumentBackOfficeUrlGenerator
                )
                ->add(
                    'oro_order.pdf_document.url_generator.storefront',
                    $this->orderPdfDocumentStorefrontUrlGenerator
                )
                ->getContainer($this)
        );
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        self::assertCount(4, $filters);
        self::assertEquals(
            'oro_order_pdf_document_back_office_url',
            $filters[0]->getName()
        );
        self::assertEquals(
            'oro_order_pdf_document_order_default_back_office_url',
            $filters[1]->getName()
        );
        self::assertEquals(
            'oro_order_pdf_document_storefront_url',
            $filters[2]->getName()
        );
        self::assertEquals(
            'oro_order_pdf_document_order_default_storefront_url',
            $filters[3]->getName()
        );
    }

    public function testGetPdfDocumentBackOfficeUrlReturnsValidUrl(): void
    {
        $order = new Order();
        $pdfDocumentType = 'us_standard_order';
        $expectedUrl = 'https://example.com/pdf-document/download/123';

        $this->orderPdfDocumentBackOfficeUrlGenerator
            ->expects(self::once())
            ->method('generateUrl')
            ->with($order, $pdfDocumentType)
            ->willReturn($expectedUrl);

        $result = self::callTwigFilter(
            $this->extension,
            'oro_order_pdf_document_back_office_url',
            [$order, $pdfDocumentType]
        );

        self::assertSame($expectedUrl, $result);
    }

    public function testGetPdfDocumentOrderDefaultBackOfficeUrlReturnsValidUrl(): void
    {
        $order = new Order();
        $defaultPdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $expectedUrl = 'https://example.com/pdf-document/download/42';

        $this->orderPdfDocumentBackOfficeUrlGenerator
            ->expects(self::once())
            ->method('generateUrl')
            ->with($order, $defaultPdfDocumentType)
            ->willReturn($expectedUrl);

        $result = self::callTwigFilter(
            $this->extension,
            'oro_order_pdf_document_order_default_back_office_url',
            [$order]
        );

        self::assertSame($expectedUrl, $result);
    }

    public function testGetPdfDocumentBackOfficeUrlReturnsNullForNonExistentDocument(): void
    {
        $order = new Order();
        $pdfDocumentType = 'us_standard_order';

        $this->orderPdfDocumentBackOfficeUrlGenerator
            ->expects(self::once())
            ->method('generateUrl')
            ->with($order, $pdfDocumentType)
            ->willReturn(null);

        $result = self::callTwigFilter(
            $this->extension,
            'oro_order_pdf_document_back_office_url',
            [$order, $pdfDocumentType]
        );

        self::assertNull($result);
    }

    public function testGetPdfDocumentOrderDefaultBackOfficeUrlReturnsNullForNonExistentDocument(): void
    {
        $order = new Order();
        $defaultPdfDocumentType = OrderPdfDocumentType::DEFAULT;

        $this->orderPdfDocumentBackOfficeUrlGenerator
            ->expects(self::once())
            ->method('generateUrl')
            ->with($order, $defaultPdfDocumentType)
            ->willReturn(null);

        $result = self::callTwigFilter(
            $this->extension,
            'oro_order_pdf_document_order_default_back_office_url',
            [$order]
        );

        self::assertNull($result);
    }

    public function testGetPdfDocumentStorefrontUrlReturnsValidUrl(): void
    {
        $order = new Order();
        $pdfDocumentType = 'us_standard_order';
        $expectedUrl = 'https://example.com/pdf-document/download/123';

        $this->orderPdfDocumentStorefrontUrlGenerator
            ->expects(self::once())
            ->method('generateUrl')
            ->with($order, $pdfDocumentType)
            ->willReturn($expectedUrl);

        $result = self::callTwigFilter(
            $this->extension,
            'oro_order_pdf_document_storefront_url',
            [$order, $pdfDocumentType]
        );

        self::assertSame($expectedUrl, $result);
    }

    public function testGetPdfDocumentOrderDefaultStorefrontUrlReturnsValidUrl(): void
    {
        $order = new Order();
        $defaultPdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $expectedUrl = 'https://example.com/pdf-document/download/42';

        $this->orderPdfDocumentStorefrontUrlGenerator
            ->expects(self::once())
            ->method('generateUrl')
            ->with($order, $defaultPdfDocumentType)
            ->willReturn($expectedUrl);

        $result = self::callTwigFilter(
            $this->extension,
            'oro_order_pdf_document_order_default_storefront_url',
            [$order]
        );

        self::assertSame($expectedUrl, $result);
    }

    public function testGetPdfDocumentStorefrontUrlReturnsNullForNonExistentDocument(): void
    {
        $order = new Order();
        $pdfDocumentType = 'us_standard_order';

        $this->orderPdfDocumentStorefrontUrlGenerator
            ->expects(self::once())
            ->method('generateUrl')
            ->with($order, $pdfDocumentType)
            ->willReturn(null);

        $result = self::callTwigFilter(
            $this->extension,
            'oro_order_pdf_document_storefront_url',
            [$order, $pdfDocumentType]
        );

        self::assertNull($result);
    }

    public function testGetPdfDocumentOrderDefaultStorefrontUrlReturnsNullForNonExistentDocument(): void
    {
        $order = new Order();
        $defaultPdfDocumentType = OrderPdfDocumentType::DEFAULT;

        $this->orderPdfDocumentStorefrontUrlGenerator
            ->expects(self::once())
            ->method('generateUrl')
            ->with($order, $defaultPdfDocumentType)
            ->willReturn(null);

        $result = self::callTwigFilter(
            $this->extension,
            'oro_order_pdf_document_order_default_storefront_url',
            [$order]
        );

        self::assertNull($result);
    }
}
