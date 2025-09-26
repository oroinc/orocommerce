<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\PdfDocument\Manager;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\Demand\GenericOrderPdfDocumentDemand;
use Oro\Bundle\OrderBundle\PdfDocument\Manager\OrderPdfDocumentManager;
use Oro\Bundle\OrderBundle\PdfDocument\OrderPdfDocumentType;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Name\PdfDocumentNameProviderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator\PdfDocumentOperatorInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator\PdfDocumentOperatorRegistry;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfDocumentGenerationMode;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Provider\SinglePdfDocumentBySourceEntityProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderPdfDocumentManagerTest extends TestCase
{
    private OrderPdfDocumentManager $manager;

    private MockObject&SinglePdfDocumentBySourceEntityProviderInterface $singlePdfDocumentBySourceEntityProvider;

    private MockObject&PdfDocumentOperatorRegistry $pdfDocumentOperatorRegistry;

    private MockObject&PdfDocumentNameProviderInterface $pdfDocumentNameProvider;

    private string $pdfDocumentGenerationMode;

    protected function setUp(): void
    {
        $this->singlePdfDocumentBySourceEntityProvider = $this->createMock(
            SinglePdfDocumentBySourceEntityProviderInterface::class
        );
        $this->pdfDocumentOperatorRegistry = $this->createMock(PdfDocumentOperatorRegistry::class);
        $this->pdfDocumentNameProvider = $this->createMock(PdfDocumentNameProviderInterface::class);
        $this->pdfDocumentGenerationMode = PdfDocumentGenerationMode::DEFERRED;

        $this->manager = new OrderPdfDocumentManager(
            $this->singlePdfDocumentBySourceEntityProvider,
            $this->pdfDocumentOperatorRegistry,
            $this->pdfDocumentNameProvider,
            $this->pdfDocumentGenerationMode
        );
    }

    public function testHasPdfDocumentReturnsTrueWhenDocumentExists(): void
    {
        $order = new Order();
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfDocumentName = 'order-0001';

        $this->pdfDocumentNameProvider
            ->expects(self::once())
            ->method('createPdfDocumentName')
            ->with($order)
            ->willReturn($pdfDocumentName);

        $this->singlePdfDocumentBySourceEntityProvider
            ->expects(self::once())
            ->method('findPdfDocument')
            ->with($order, $pdfDocumentName, $pdfDocumentType)
            ->willReturn(new PdfDocument());

        self::assertTrue($this->manager->hasPdfDocument($order, $pdfDocumentType));
    }

    public function testHasPdfDocumentReturnsFalseWhenDocumentNotExists(): void
    {
        $order = new Order();
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfDocumentName = 'order-0001';

        $this->pdfDocumentNameProvider
            ->expects(self::once())
            ->method('createPdfDocumentName')
            ->with($order)
            ->willReturn($pdfDocumentName);

        $this->singlePdfDocumentBySourceEntityProvider
            ->expects(self::once())
            ->method('findPdfDocument')
            ->with($order, $pdfDocumentName, $pdfDocumentType)
            ->willReturn(null);

        self::assertFalse($this->manager->hasPdfDocument($order, $pdfDocumentType));
    }

    public function testRetrievePdfDocumentReturnsDocumentWhenExists(): void
    {
        $order = new Order();
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfDocumentName = 'order-0001';
        $pdfDocument = new PdfDocument();

        $this->pdfDocumentNameProvider
            ->expects(self::once())
            ->method('createPdfDocumentName')
            ->with($order)
            ->willReturn($pdfDocumentName);

        $this->singlePdfDocumentBySourceEntityProvider
            ->expects(self::once())
            ->method('findPdfDocument')
            ->with($order, $pdfDocumentName, $pdfDocumentType)
            ->willReturn($pdfDocument);

        $pdfDocumentOperator = $this->createMock(PdfDocumentOperatorInterface::class);
        $pdfDocumentOperator
            ->expects(self::once())
            ->method('resolvePdfDocument')
            ->with($pdfDocument);

        $this->pdfDocumentOperatorRegistry
            ->expects(self::once())
            ->method('getOperator')
            ->with(Order::class, $this->pdfDocumentGenerationMode)
            ->willReturn($pdfDocumentOperator);

        $result = $this->manager->retrievePdfDocument($order, $pdfDocumentType);

        self::assertInstanceOf(PdfDocument::class, $result);
        self::assertSame($pdfDocument, $result);
    }

    public function testRetrievePdfDocumentReturnsNullWhenDocumentDoesNotExist(): void
    {
        $order = new Order();
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfDocumentName = 'order-0001';

        $this->pdfDocumentNameProvider
            ->expects(self::once())
            ->method('createPdfDocumentName')
            ->with($order)
            ->willReturn($pdfDocumentName);

        $this->singlePdfDocumentBySourceEntityProvider
            ->expects(self::once())
            ->method('findPdfDocument')
            ->with($order, $pdfDocumentName, $pdfDocumentType)
            ->willReturn(null);

        $result = $this->manager->retrievePdfDocument($order, $pdfDocumentType);

        self::assertNull($result);
    }

    public function testCreatePdfDocumentCreatesAndReturnsNewDocument(): void
    {
        $order = new Order();
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfDocumentName = 'order-0001';
        $pdfOptionsPreset = 'default';
        $pdfDocumentPayload = ['key' => 'value'];
        $pdfDocument = new PdfDocument();

        $orderPdfDocumentDemand = new GenericOrderPdfDocumentDemand(
            $order,
            $pdfDocumentType,
            $pdfOptionsPreset,
            $pdfDocumentPayload
        );

        $this->pdfDocumentNameProvider
            ->expects(self::once())
            ->method('createPdfDocumentName')
            ->with($order)
            ->willReturn($pdfDocumentName);

        $pdfDocumentOperator = $this->createMock(PdfDocumentOperatorInterface::class);
        $pdfDocumentOperator
            ->expects(self::once())
            ->method('createPdfDocument')
            ->with($orderPdfDocumentDemand)
            ->willReturn($pdfDocument);

        $this->pdfDocumentOperatorRegistry
            ->expects(self::once())
            ->method('getOperator')
            ->with(Order::class, $this->pdfDocumentGenerationMode)
            ->willReturn($pdfDocumentOperator);

        $result = $this->manager->createPdfDocument($orderPdfDocumentDemand);

        self::assertInstanceOf(PdfDocument::class, $result);
        self::assertSame($pdfDocument, $result);
        self::assertContains($pdfDocument, $order->getPdfDocuments());
    }

    public function testCreatePdfDocumentCreatesAndReturnsNewDocumentWhenNameIsAlreadySet(): void
    {
        $website = new Website();
        $order = new Order();
        $order->setWebsite($website);
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfDocumentName = 'order-0001';
        $pdfOptionsPreset = 'default';
        $pdfDocumentPayload = ['key' => 'value'];
        $pdfDocument = new PdfDocument();

        $orderPdfDocumentDemand = new GenericOrderPdfDocumentDemand(
            $order,
            $pdfDocumentType,
            $pdfOptionsPreset,
            $pdfDocumentPayload
        );
        $orderPdfDocumentDemand->setPdfDocumentName($pdfDocumentName);

        $this->pdfDocumentNameProvider
            ->expects(self::never())
            ->method('createPdfDocumentName');

        $pdfDocumentOperator = $this->createMock(PdfDocumentOperatorInterface::class);
        $pdfDocumentOperator
            ->expects(self::once())
            ->method('createPdfDocument')
            ->with($orderPdfDocumentDemand)
            ->willReturn($pdfDocument);

        $this->pdfDocumentOperatorRegistry
            ->expects(self::once())
            ->method('getOperator')
            ->with(Order::class, $this->pdfDocumentGenerationMode)
            ->willReturn($pdfDocumentOperator);

        $result = $this->manager->createPdfDocument($orderPdfDocumentDemand);

        self::assertInstanceOf(PdfDocument::class, $result);
        self::assertSame($pdfDocument, $result);
        self::assertContains($pdfDocument, $order->getPdfDocuments());
    }

    public function testUpdatePdfDocumentReturnsNullWhenDocumentDoesNotExist(): void
    {
        $order = new Order();
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfDocumentName = 'order-0001';

        $this->pdfDocumentNameProvider
            ->expects(self::once())
            ->method('createPdfDocumentName')
            ->with($order)
            ->willReturn($pdfDocumentName);

        $this->singlePdfDocumentBySourceEntityProvider
            ->expects(self::once())
            ->method('findPdfDocument')
            ->with($order, $pdfDocumentName, $pdfDocumentType)
            ->willReturn(null);

        $this->pdfDocumentOperatorRegistry
            ->expects(self::never())
            ->method('getOperator');

        $result = $this->manager->updatePdfDocument($order, $pdfDocumentType);

        self::assertNull($result);
    }

    public function testUpdatePdfDocumentReturnsUpdatedDocumentWhenDocumentExists(): void
    {
        $order = new Order();
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfDocumentName = 'order-0001';
        $pdfDocument = new PdfDocument();

        $this->pdfDocumentNameProvider
            ->expects(self::once())
            ->method('createPdfDocumentName')
            ->with($order)
            ->willReturn($pdfDocumentName);

        $this->singlePdfDocumentBySourceEntityProvider
            ->expects(self::once())
            ->method('findPdfDocument')
            ->with($order, $pdfDocumentName, $pdfDocumentType)
            ->willReturn($pdfDocument);

        $pdfDocumentOperator = $this->createMock(PdfDocumentOperatorInterface::class);
        $pdfDocumentOperator
            ->expects(self::once())
            ->method('updatePdfDocument')
            ->with($pdfDocument);

        $this->pdfDocumentOperatorRegistry
            ->expects(self::once())
            ->method('getOperator')
            ->with(Order::class, $this->pdfDocumentGenerationMode)
            ->willReturn($pdfDocumentOperator);

        $result = $this->manager->updatePdfDocument($order, $pdfDocumentType);

        self::assertInstanceOf(PdfDocument::class, $result);
        self::assertSame($pdfDocument, $result);
    }

    public function testDeletePdfDocumentReturnsNullWhenDocumentDoesNotExist(): void
    {
        $order = new Order();
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfDocumentName = 'order-0001';

        $this->pdfDocumentNameProvider
            ->expects(self::once())
            ->method('createPdfDocumentName')
            ->with($order)
            ->willReturn($pdfDocumentName);

        $this->singlePdfDocumentBySourceEntityProvider
            ->expects(self::once())
            ->method('findPdfDocument')
            ->with($order, $pdfDocumentName, $pdfDocumentType)
            ->willReturn(null);

        $this->pdfDocumentOperatorRegistry
            ->expects(self::never())
            ->method('getOperator');

        $result = $this->manager->deletePdfDocument($order, $pdfDocumentType);

        self::assertNull($result);
    }

    public function testDeletePdfDocumentReturnsDeletedDocumentWhenDocumentExists(): void
    {
        $order = new Order();
        $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
        $pdfDocumentName = 'order-0001';
        $pdfDocument = new PdfDocument();

        $this->pdfDocumentNameProvider
            ->expects(self::once())
            ->method('createPdfDocumentName')
            ->with($order)
            ->willReturn($pdfDocumentName);

        $this->singlePdfDocumentBySourceEntityProvider
            ->expects(self::once())
            ->method('findPdfDocument')
            ->with($order, $pdfDocumentName, $pdfDocumentType)
            ->willReturn($pdfDocument);

        $pdfDocumentOperator = $this->createMock(PdfDocumentOperatorInterface::class);
        $pdfDocumentOperator
            ->expects(self::once())
            ->method('deletePdfDocument')
            ->with($pdfDocument);

        $this->pdfDocumentOperatorRegistry
            ->expects(self::once())
            ->method('getOperator')
            ->with(Order::class, $this->pdfDocumentGenerationMode)
            ->willReturn($pdfDocumentOperator);

        $order->addPdfDocument($pdfDocument);

        $result = $this->manager->deletePdfDocument($order, $pdfDocumentType);

        self::assertInstanceOf(PdfDocument::class, $result);
        self::assertSame($pdfDocument, $result);
        self::assertNotContains($pdfDocument, $order->getPdfDocuments());
    }
}
