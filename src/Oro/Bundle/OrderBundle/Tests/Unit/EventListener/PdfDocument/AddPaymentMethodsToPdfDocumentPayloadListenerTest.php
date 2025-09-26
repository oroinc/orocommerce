<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\PdfDocument;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\PdfDocument\AddPaymentMethodsToPdfDocumentPayloadListener;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AddPaymentMethodsToPdfDocumentPayloadListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private PaymentTransactionProvider&MockObject $paymentTransactionProvider;
    private PdfBuilderInterface&MockObject $pdfBuilder;
    private AddPaymentMethodsToPdfDocumentPayloadListener $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->pdfBuilder = $this->createMock(PdfBuilderInterface::class);

        $this->listener = new AddPaymentMethodsToPdfDocumentPayloadListener(
            $this->doctrine,
            $this->paymentTransactionProvider
        );
    }

    public function testOnBeforePdfDocumentGeneratedWithOrderEntity(): void
    {
        $orderId = 123;
        $paymentMethods = ['credit_card', 'paypal'];
        $originalPayload = ['existing' => 'data'];
        $expectedPayload = ['existing' => 'data', 'paymentMethods' => $paymentMethods];

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

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('getPaymentMethods')
            ->with($order)
            ->willReturn($paymentMethods);

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

        $this->paymentTransactionProvider
            ->expects(self::never())
            ->method('getPaymentMethods');

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

        $this->paymentTransactionProvider
            ->expects(self::never())
            ->method('getPaymentMethods');

        $this->listener->onBeforePdfDocumentGenerated($event);

        self::assertEquals($originalPayload, $event->getPdfDocumentPayload());
    }

    public function testOnBeforePdfDocumentGeneratedWithEmptyPaymentMethods(): void
    {
        $orderId = 456;
        $paymentMethods = [];
        $originalPayload = ['existing' => 'data'];
        $expectedPayload = ['existing' => 'data', 'paymentMethods' => []];

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

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('getPaymentMethods')
            ->with($order)
            ->willReturn($paymentMethods);

        $this->listener->onBeforePdfDocumentGenerated($event);

        self::assertEquals($expectedPayload, $event->getPdfDocumentPayload());
    }
}
