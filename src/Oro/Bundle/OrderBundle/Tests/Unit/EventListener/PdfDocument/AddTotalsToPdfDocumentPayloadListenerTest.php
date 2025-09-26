<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\PdfDocument;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\PdfDocument\AddTotalsToPdfDocumentPayloadListener;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AddTotalsToPdfDocumentPayloadListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private TotalProcessorProvider&MockObject $totalProcessorProvider;
    private PdfBuilderInterface&MockObject $pdfBuilder;
    private AddTotalsToPdfDocumentPayloadListener $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->totalProcessorProvider = $this->createMock(TotalProcessorProvider::class);
        $this->pdfBuilder = $this->createMock(PdfBuilderInterface::class);

        $this->listener = new AddTotalsToPdfDocumentPayloadListener(
            $this->doctrine,
            $this->totalProcessorProvider
        );
    }

    public function testOnBeforePdfDocumentGeneratedWithOrderEntity(): void
    {
        $orderId = 123;
        $totalsData = [
            'total' => [
                'type' => 'total',
                'label' => 'Total',
                'amount' => 100.00,
                'currency' => 'USD',
                'visible' => true,
                'data' => [],
            ],
            'subtotals' => [
                [
                    'type' => 'subtotal',
                    'label' => 'Subtotal',
                    'amount' => 80.00,
                    'currency' => 'USD',
                    'visible' => true,
                    'data' => [],
                ],
                [
                    'type' => 'tax',
                    'label' => 'Tax',
                    'amount' => 20.00,
                    'currency' => 'USD',
                    'visible' => true,
                    'data' => [],
                ],
            ],
        ];
        $originalPayload = ['existing' => 'data'];
        $expectedPayload = ['existing' => 'data', 'totals' => $totalsData];

        $order = new Order();
        $order->setIdentifier('ORDER-001');

        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityClass(Order::class);
        $pdfDocument->setSourceEntityId($orderId);

        $objectManager = $this->createMock(ObjectManager::class);
        $event = new BeforePdfDocumentGeneratedEvent($this->pdfBuilder, $pdfDocument, $originalPayload);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($objectManager);

        $objectManager
            ->expects(self::once())
            ->method('find')
            ->with(Order::class, $orderId)
            ->willReturn($order);

        $this->totalProcessorProvider
            ->expects(self::once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($order)
            ->willReturn($totalsData);

        $this->listener->onBeforePdfDocumentGenerated($event);

        self::assertEquals($expectedPayload, $event->getPdfDocumentPayload());
    }

    public function testOnBeforePdfDocumentGeneratedWithNonOrderEntity(): void
    {
        $originalPayload = ['existing' => 'data'];

        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityClass('Some\Other\Entity');

        $event = new BeforePdfDocumentGeneratedEvent($this->pdfBuilder, $pdfDocument, $originalPayload);

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->totalProcessorProvider
            ->expects(self::never())
            ->method('getTotalWithSubtotalsAsArray');

        $this->listener->onBeforePdfDocumentGenerated($event);

        self::assertEquals($originalPayload, $event->getPdfDocumentPayload());
    }

    public function testOnBeforePdfDocumentGeneratedWithOrderNotFound(): void
    {
        $orderId = 999;
        $originalPayload = ['existing' => 'data'];

        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityClass(Order::class);
        $pdfDocument->setSourceEntityId($orderId);

        $objectManager = $this->createMock(ObjectManager::class);
        $event = new BeforePdfDocumentGeneratedEvent($this->pdfBuilder, $pdfDocument, $originalPayload);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($objectManager);

        $objectManager
            ->expects(self::once())
            ->method('find')
            ->with(Order::class, $orderId)
            ->willReturn(null);

        $this->totalProcessorProvider
            ->expects(self::never())
            ->method('getTotalWithSubtotalsAsArray');

        $this->listener->onBeforePdfDocumentGenerated($event);

        self::assertEquals($originalPayload, $event->getPdfDocumentPayload());
    }

    public function testOnBeforePdfDocumentGeneratedWithEmptyTotals(): void
    {
        $orderId = 456;
        $totalsData = [
            'total' => [
                'type' => 'total',
                'label' => 'Total',
                'amount' => 0.00,
                'currency' => 'USD',
                'visible' => false,
                'data' => [],
            ],
            'subtotals' => [],
        ];
        $originalPayload = ['existing' => 'data'];
        $expectedPayload = ['existing' => 'data', 'totals' => $totalsData];

        $order = new Order();
        $order->setIdentifier('ORDER-002');

        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityClass(Order::class);
        $pdfDocument->setSourceEntityId($orderId);

        $objectManager = $this->createMock(ObjectManager::class);
        $event = new BeforePdfDocumentGeneratedEvent($this->pdfBuilder, $pdfDocument, $originalPayload);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($objectManager);

        $objectManager
            ->expects(self::once())
            ->method('find')
            ->with(Order::class, $orderId)
            ->willReturn($order);

        $this->totalProcessorProvider
            ->expects(self::once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($order)
            ->willReturn($totalsData);

        $this->listener->onBeforePdfDocumentGenerated($event);

        self::assertEquals($expectedPayload, $event->getPdfDocumentPayload());
    }
}
