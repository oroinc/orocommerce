<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\EntityDetachFixer;
use Oro\Bundle\ProductBundle\ImportExport\Writer\RelatedProductEntityWriter;

class RelatedProductEntityWriterTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityDetachFixer|\PHPUnit\Framework\MockObject\MockObject */
    private $detachFixer;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var RelatedProductEntityWriter */
    private $writer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->detachFixer = $this->createMock(EntityDetachFixer::class);
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->writer = new RelatedProductEntityWriter(
            $this->doctrineHelper,
            $this->detachFixer,
            $this->contextRegistry
        );
    }

    public function testWrite(): void
    {
        $firstItem = $this->createMock(\stdClass::class);
        $secondItem = $this->createMock(\ArrayObject::class);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive([$firstItem], [$secondItem]);
        $this->entityManager->expects($this->once())
            ->method('flush');
        $this->entityManager->expects($this->once())
            ->method('clear');

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([]);

        $stepExecution = $this->createMock(StepExecution::class);

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->writer->setStepExecution($stepExecution);
        $this->writer->write([[$firstItem], [$secondItem]]);
    }
}
