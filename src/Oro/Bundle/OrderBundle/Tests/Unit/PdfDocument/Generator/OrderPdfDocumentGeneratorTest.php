<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\PdfDocument\Generator;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\Generator\OrderPdfDocumentGenerator;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfDocumentSourceEntityNotFound;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Generator\PdfDocumentGeneratorInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderPdfDocumentGeneratorTest extends TestCase
{
    private OrderPdfDocumentGenerator $generator;

    private MockObject&PdfDocumentGeneratorInterface $orderPdfDocumentGenerator;

    private MockObject&ManagerRegistry $doctrine;

    private MockObject&WebsiteManager $websiteManager;

    private MockObject&PreferredLocalizationProviderInterface $preferredLocalizationProvider;

    private MockObject&LocalizationProviderInterface $localizationProvider;

    private string $pdfDocumentType;

    protected function setUp(): void
    {
        $this->orderPdfDocumentGenerator = $this->createMock(PdfDocumentGeneratorInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->preferredLocalizationProvider = $this->createMock(PreferredLocalizationProviderInterface::class);
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $this->pdfDocumentType = 'order_default';

        $this->generator = new OrderPdfDocumentGenerator(
            $this->orderPdfDocumentGenerator,
            $this->doctrine,
            $this->websiteManager,
            $this->preferredLocalizationProvider,
            $this->localizationProvider,
            $this->pdfDocumentType
        );
    }

    public function testIsApplicableReturnsTrueForApplicablePdfDocument(): void
    {
        $pdfDocument = (new PdfDocument())
            ->setSourceEntityClass(Order::class)
            ->setPdfDocumentType($this->pdfDocumentType);

        $this->orderPdfDocumentGenerator
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(true);

        $result = $this->generator->isApplicable($pdfDocument);

        self::assertTrue($result);
    }

    public function testIsApplicableReturnsFalseForNonApplicablePdfDocument(): void
    {
        $pdfDocument = (new PdfDocument())
            ->setSourceEntityClass(Order::class)
            ->setPdfDocumentType($this->pdfDocumentType);

        $this->orderPdfDocumentGenerator
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(false);

        $result = $this->generator->isApplicable($pdfDocument);

        self::assertFalse($result);
    }

    public function testIsApplicableReturnsFalseForNonApplicablePdfDocumentType(): void
    {
        $pdfDocument = (new PdfDocument())
            ->setSourceEntityClass(Order::class)
            ->setPdfDocumentType('non_applicable_type');

        $this->orderPdfDocumentGenerator
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(true);

        $result = $this->generator->isApplicable($pdfDocument);

        self::assertFalse($result);
    }

    public function testIsApplicableReturnsFalseForNonApplicableSourceEntityClass(): void
    {
        $pdfDocument = (new PdfDocument())
            ->setSourceEntityClass(\stdClass::class)
            ->setPdfDocumentType($this->pdfDocumentType);

        $this->orderPdfDocumentGenerator
            ->expects(self::once())
            ->method('isApplicable')
            ->with($pdfDocument)
            ->willReturn(true);

        $result = $this->generator->isApplicable($pdfDocument);

        self::assertFalse($result);
    }

    public function testGeneratePdfFileSwitchesToOrderWebsiteAndLocalization(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityId(1);
        $pdfDocument->setSourceEntityClass(Order::class);

        $orderWebsite = new Website();

        $order = new Order();
        ReflectionUtil::setId($order, 42);
        $order->setWebsite($orderWebsite);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($entityRepository);

        $this->websiteManager
            ->expects(self::never())
            ->method('getDefaultWebsite');

        $originalWebsite = new Website();
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($originalWebsite);

        $originalLocalization = new Localization();
        $this->localizationProvider
            ->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn($originalLocalization);

        $orderLocalization = new Localization();
        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with($order)
            ->willReturn($orderLocalization);

        $this->websiteManager
            ->expects(self::exactly(2))
            ->method('setCurrentWebsite')
            ->withConsecutive([$orderWebsite], [$originalWebsite]);

        $this->localizationProvider
            ->expects(self::exactly(2))
            ->method('setCurrentLocalization')
            ->withConsecutive([$orderLocalization], [$originalLocalization]);

        $pdfFile = $this->createMock(PdfFileInterface::class);
        $this->orderPdfDocumentGenerator
            ->expects(self::once())
            ->method('generatePdfFile')
            ->with($pdfDocument)
            ->willReturn($pdfFile);

        $result = $this->generator->generatePdfFile($pdfDocument);

        self::assertSame($pdfFile, $result);
    }

    public function testGeneratePdfFileSwitchesToDefaultWebsiteAndLocalization(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityId(1);
        $pdfDocument->setSourceEntityClass(Order::class);

        $order = new Order();
        ReflectionUtil::setId($order, 42);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($entityRepository);

        $defaultWebsite = new Website();
        $this->websiteManager
            ->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($defaultWebsite);

        $originalWebsite = new Website();
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($originalWebsite);

        $originalLocalization = new Localization();
        $this->localizationProvider
            ->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn($originalLocalization);

        $orderLocalization = new Localization();
        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with($order)
            ->willReturn($orderLocalization);

        $this->websiteManager
            ->expects(self::exactly(2))
            ->method('setCurrentWebsite')
            ->withConsecutive([$defaultWebsite], [$originalWebsite]);

        $this->localizationProvider
            ->expects(self::exactly(2))
            ->method('setCurrentLocalization')
            ->withConsecutive([$orderLocalization], [$originalLocalization]);

        $pdfFile = $this->createMock(PdfFileInterface::class);
        $this->orderPdfDocumentGenerator
            ->expects(self::once())
            ->method('generatePdfFile')
            ->with($pdfDocument)
            ->willReturn($pdfFile);

        $result = $this->generator->generatePdfFile($pdfDocument);

        self::assertSame($pdfFile, $result);
    }

    public function testGeneratePdfFileThrowsExceptionWhenOrderNotFound(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityId(1);
        $pdfDocument->setSourceEntityClass(Order::class);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($entityRepository);

        $this->expectException(PdfDocumentSourceEntityNotFound::class);
        $this->expectExceptionMessage(
            'The source entity "Oro\\Bundle\\OrderBundle\\Entity\\Order" with ID "1" was not found.'
        );

        $this->generator->generatePdfFile($pdfDocument);
    }

    public function testGeneratePdfFileHandlesNullSourceEntityIdGracefully(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityId(null);
        $pdfDocument->setSourceEntityClass(Order::class);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository
            ->expects(self::once())
            ->method('find')
            ->with(0)
            ->willReturn(null);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($entityRepository);

        $this->expectException(PdfDocumentSourceEntityNotFound::class);
        $this->expectExceptionMessage(
            'The source entity "Oro\\Bundle\\OrderBundle\\Entity\\Order" with ID "" was not found.'
        );

        $this->generator->generatePdfFile($pdfDocument);
    }

    public function testGeneratePdfFileRestoresOriginalStateAfterFailure(): void
    {
        $pdfDocument = new PdfDocument();
        $pdfDocument->setSourceEntityId(1);
        $pdfDocument->setSourceEntityClass(Order::class);

        $orderWebsite = new Website();

        $order = new Order();
        ReflectionUtil::setId($order, 42);
        $order->setWebsite($orderWebsite);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($entityRepository);

        $originalWebsite = new Website();
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($originalWebsite);

        $originalLocalization = new Localization();
        $this->localizationProvider
            ->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn($originalLocalization);

        $orderLocalization = new Localization();
        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with($order)
            ->willReturn($orderLocalization);

        $this->websiteManager
            ->expects(self::exactly(2))
            ->method('setCurrentWebsite')
            ->withConsecutive([$orderWebsite], [$originalWebsite]);

        $this->localizationProvider
            ->expects(self::exactly(2))
            ->method('setCurrentLocalization')
            ->withConsecutive([$orderLocalization], [$originalLocalization]);

        $this->orderPdfDocumentGenerator
            ->expects(self::once())
            ->method('generatePdfFile')
            ->with($pdfDocument)
            ->willThrowException(new \RuntimeException('PDF generation failed.'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PDF generation failed.');

        $this->generator->generatePdfFile($pdfDocument);
    }
}
