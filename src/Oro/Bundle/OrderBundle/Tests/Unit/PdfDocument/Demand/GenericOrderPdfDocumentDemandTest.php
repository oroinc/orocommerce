<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\PdfDocument\Demand;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\Demand\GenericOrderPdfDocumentDemand;
use Oro\Bundle\OrderBundle\PdfDocument\OrderPdfDocumentType;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;
use PHPUnit\Framework\TestCase;

final class GenericOrderPdfDocumentDemandTest extends TestCase
{
    public function testConstructorInitializesPropertiesCorrectly(): void
    {
        $order = new Order();
        $pdfDocumentName = 'sample-order-001';
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfOptionsPreset = PdfOptionsPreset::DEFAULT;
        $pdfDocumentPayload = ['key' => 'value'];

        $demand = new GenericOrderPdfDocumentDemand(
            $order,
            $pdfDocumentType,
            $pdfOptionsPreset,
            $pdfDocumentPayload
        );
        $demand->setPdfDocumentName($pdfDocumentName);

        self::assertSame($order, $demand->getSourceEntity());
        self::assertSame($pdfDocumentName, $demand->getPdfDocumentName());
        self::assertSame($pdfDocumentType, $demand->getPdfDocumentType());
        self::assertSame($pdfOptionsPreset, $demand->getPdfOptionsPreset());
        self::assertSame($pdfDocumentPayload, $demand->getPdfDocumentPayload());
    }

    public function testConstructorWithDefaultParameters(): void
    {
        $order = new Order();
        $pdfDocumentName = 'sample-order-001';

        $demand = new GenericOrderPdfDocumentDemand($order);
        $demand->setPdfDocumentName($pdfDocumentName);

        self::assertSame($order, $demand->getSourceEntity());
        self::assertSame($pdfDocumentName, $demand->getPdfDocumentName());
        self::assertSame(OrderPdfDocumentType::DEFAULT, $demand->getPdfDocumentType());
        self::assertSame(PdfOptionsPreset::DEFAULT, $demand->getPdfOptionsPreset());
        self::assertSame([], $demand->getPdfDocumentPayload());
    }

    public function testSetPdfDocumentPayloadUpdatesPayload(): void
    {
        $order = new Order();
        $demand = new GenericOrderPdfDocumentDemand($order);

        $newPayload = ['newKey' => 'newValue'];
        $demand->setPdfDocumentPayload($newPayload);

        self::assertSame($newPayload, $demand->getPdfDocumentPayload());
    }

    public function testSetPdfOptionsPresetUpdatesPreset(): void
    {
        $order = new Order();
        $demand = new GenericOrderPdfDocumentDemand($order);

        $newPreset = 'custom_preset';
        $demand->setPdfOptionsPreset($newPreset);

        self::assertSame($newPreset, $demand->getPdfOptionsPreset());
    }

    public function testSetPdfDocumentNameThrowsExceptionIfAlreadySet(): void
    {
        $order = new Order();
        $demand = new GenericOrderPdfDocumentDemand($order);

        $demand->setPdfDocumentName('order-001');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The PDF document name is already set.');

        $demand->setPdfDocumentName('order-002');
    }

    public function testSetPdfDocumentNameSetsNameWhenNotAlreadySet(): void
    {
        $order = new Order();
        $demand = new GenericOrderPdfDocumentDemand($order);

        $pdfDocumentName = 'order-001';
        $demand->setPdfDocumentName($pdfDocumentName);

        self::assertSame($pdfDocumentName, $demand->getPdfDocumentName());
    }
}
