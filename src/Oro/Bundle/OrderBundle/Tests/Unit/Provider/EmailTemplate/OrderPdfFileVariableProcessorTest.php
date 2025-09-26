<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider\EmailTemplate;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use Oro\Bundle\OrderBundle\PdfDocument\Manager\OrderPdfDocumentManagerInterface;
use Oro\Bundle\OrderBundle\Provider\EmailTemplate\OrderPdfFileVariableProcessor;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderPdfFileVariableProcessorTest extends TestCase
{
    private const string VARIABLE_NAME = 'orderDefaultPdfFile';
    private const string PDF_DOCUMENT_TYPE = 'order_default';
    private const string PARENT_VARIABLE_PATH = 'order';

    private OrderPdfFileVariableProcessor $processor;

    private MockObject&OrderPdfDocumentManagerInterface $orderPdfDocumentManager;
    private MockObject&ManagerRegistry $doctrine;
    private MockObject&TemplateData $templateData;
    private MockObject&EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->orderPdfDocumentManager = $this->createMock(OrderPdfDocumentManagerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->templateData = $this->createMock(TemplateData::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->doctrine
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(PdfDocument::class)
            ->willReturn($this->entityManager);

        $this->processor = new OrderPdfFileVariableProcessor(
            $this->orderPdfDocumentManager,
            $this->doctrine,
            self::PDF_DOCUMENT_TYPE,
        );
    }

    public function testProcessWithNonOrderEntity(): void
    {
        $this->templateData
            ->expects(self::once())
            ->method('getParentVariablePath')
            ->with(self::VARIABLE_NAME)
            ->willReturn(self::PARENT_VARIABLE_PATH);

        $this->templateData
            ->expects(self::once())
            ->method('getEntityVariable')
            ->with(self::PARENT_VARIABLE_PATH)
            ->willReturn(new \stdClass());

        $this->templateData
            ->expects(self::once())
            ->method('setComputedVariable')
            ->with(self::VARIABLE_NAME, null);

        $this->orderPdfDocumentManager
            ->expects(self::never())
            ->method('hasPdfDocument');

        $this->entityManager
            ->expects(self::never())
            ->method('persist');

        $this->entityManager
            ->expects(self::never())
            ->method('flush');

        $this->processor->process(self::VARIABLE_NAME, [], $this->templateData);
    }

    public function testProcessWithNullEntity(): void
    {
        $this->templateData
            ->expects(self::once())
            ->method('getParentVariablePath')
            ->with(self::VARIABLE_NAME)
            ->willReturn(self::PARENT_VARIABLE_PATH);

        $this->templateData
            ->expects(self::once())
            ->method('getEntityVariable')
            ->with(self::PARENT_VARIABLE_PATH)
            ->willReturn(null);

        $this->templateData
            ->expects(self::once())
            ->method('setComputedVariable')
            ->with(self::VARIABLE_NAME, null);

        $this->orderPdfDocumentManager
            ->expects(self::never())
            ->method('hasPdfDocument');

        $this->entityManager
            ->expects(self::never())
            ->method('persist');

        $this->entityManager
            ->expects(self::never())
            ->method('flush');

        $this->processor->process(self::VARIABLE_NAME, [], $this->templateData);
    }
}
