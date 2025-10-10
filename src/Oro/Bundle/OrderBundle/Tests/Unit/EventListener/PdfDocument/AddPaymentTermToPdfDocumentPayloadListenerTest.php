<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\PdfDocument;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\PdfDocument\AddPaymentTermToPdfDocumentPayloadListener;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\Event\BeforePdfDocumentGeneratedEvent;
use Oro\Bundle\PdfGeneratorBundle\PdfBuilder\PdfBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AddPaymentTermToPdfDocumentPayloadListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private PaymentTermAssociationProvider&MockObject $paymentTermAssociationProvider;
    private PdfBuilderInterface&MockObject $pdfBuilder;
    private AddPaymentTermToPdfDocumentPayloadListener $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->paymentTermAssociationProvider = $this->createMock(PaymentTermAssociationProvider::class);
        $this->pdfBuilder = $this->createMock(PdfBuilderInterface::class);

        $this->listener = new AddPaymentTermToPdfDocumentPayloadListener(
            $this->doctrine,
            $this->paymentTermAssociationProvider
        );
    }

    public function testOnBeforePdfDocumentGeneratedWithOrderEntity(): void
    {
        $orderId = 123;
        $associationNames = ['paymentTerm', 'customerPaymentTerm'];
        $originalPayload = ['existing' => 'data'];
        $expectedPayload = ['existing' => 'data', 'paymentTerm' => 'Net 30'];

        $order = new Order();
        $order->setIdentifier('ORDER-001');

        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityClass(Order::class);
        $pdfDocument->setSourceEntityId($orderId);

        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('Net 30');

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

        $this->paymentTermAssociationProvider
            ->expects(self::once())
            ->method('getAssociationNames')
            ->with(Order::class)
            ->willReturn($associationNames);

        $this->paymentTermAssociationProvider
            ->expects(self::once())
            ->method('getPaymentTerm')
            ->with($order, 'paymentTerm')
            ->willReturn($paymentTerm);

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

        $this->paymentTermAssociationProvider
            ->expects(self::never())
            ->method('getAssociationNames');

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

        $this->paymentTermAssociationProvider
            ->expects(self::never())
            ->method('getAssociationNames');

        $this->listener->onBeforePdfDocumentGenerated($event);

        self::assertEquals($originalPayload, $event->getPdfDocumentPayload());
    }

    public function testOnBeforePdfDocumentGeneratedWithNoPaymentTermFound(): void
    {
        $orderId = 456;
        $associationNames = ['paymentTerm', 'customerPaymentTerm'];
        $originalPayload = ['existing' => 'data'];

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

        $this->paymentTermAssociationProvider
            ->expects(self::once())
            ->method('getAssociationNames')
            ->with(Order::class)
            ->willReturn($associationNames);

        $this->paymentTermAssociationProvider
            ->expects(self::exactly(2))
            ->method('getPaymentTerm')
            ->withConsecutive([$order, 'paymentTerm'], [$order, 'customerPaymentTerm'])
            ->willReturn(null, null);

        $this->listener->onBeforePdfDocumentGenerated($event);

        self::assertEquals($originalPayload, $event->getPdfDocumentPayload());
    }
}
