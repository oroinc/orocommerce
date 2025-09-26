<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\PdfDocument;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\PdfDocument\AddPaymentStatusToPdfDocumentPayloadListener;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AddPaymentStatusToPdfDocumentPayloadListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private PaymentStatusManager&MockObject $paymentStatusManager;
    private PdfBuilderInterface&MockObject $pdfBuilder;
    private AddPaymentStatusToPdfDocumentPayloadListener $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->paymentStatusManager = $this->createMock(PaymentStatusManager::class);
        $this->pdfBuilder = $this->createMock(PdfBuilderInterface::class);

        $this->listener = new AddPaymentStatusToPdfDocumentPayloadListener(
            $this->doctrine,
            $this->paymentStatusManager
        );
    }

    public function testOnBeforePdfDocumentGeneratedWithOrderEntity(): void
    {
        $orderId = 123;
        $paymentStatusValue = 'full';
        $originalPayload = ['existing' => 'data'];
        $expectedPayload = ['existing' => 'data', 'paymentStatus' => $paymentStatusValue];

        $order = new Order();
        $order->setIdentifier('ORDER-001');

        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityClass(Order::class);
        $pdfDocument->setSourceEntityId($orderId);

        $paymentStatus = new PaymentStatus();
        $paymentStatus->setEntityClass(Order::class);
        $paymentStatus->setEntityIdentifier($orderId);
        $paymentStatus->setPaymentStatus($paymentStatusValue);

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

        $this->paymentStatusManager
            ->expects(self::once())
            ->method('getPaymentStatusForEntity')
            ->with(Order::class, $orderId)
            ->willReturn($paymentStatus);

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

        $this->paymentStatusManager
            ->expects(self::never())
            ->method('getPaymentStatusForEntity');

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

        $this->paymentStatusManager
            ->expects(self::never())
            ->method('getPaymentStatusForEntity');

        $this->listener->onBeforePdfDocumentGenerated($event);

        self::assertEquals($originalPayload, $event->getPdfDocumentPayload());
    }
}
