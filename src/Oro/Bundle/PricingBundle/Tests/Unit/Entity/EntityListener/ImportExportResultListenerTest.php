<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateDependentPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ImportExportResultListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportExportResultListenerTest extends TestCase
{
    private ManagerRegistry|MockObject $doctrine;
    private MessageProducerInterface|MockObject $producer;
    private ImportExportResultListener $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $this->listener = new ImportExportResultListener(
            $this->doctrine,
            $this->producer
        );
    }

    public function testPostPersistWithUnsupportedImportExportResult(): void
    {
        $importExportResult = $this->createMock(ImportExportResult::class);
        $importExportResult->expects($this->any())
            ->method('getOptions')
            ->willReturn([]);

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');
        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithMissingPriceListId(): void
    {
        $importExportResult = $this->createMock(ImportExportResult::class);
        $importExportResult->expects($this->any())
            ->method('getOptions')
            ->willReturn(['importVersion' => 1]);

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');
        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithMissingImportVersion(): void
    {
        $importExportResult = $this->createMock(ImportExportResult::class);
        $importExportResult->expects($this->any())
            ->method('getOptions')
            ->willReturn(['price_list_id' => 1]);

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');
        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithNullEntityManager(): void
    {
        $importExportResult = $this->createMock(ImportExportResult::class);
        $importExportResult->expects($this->any())
            ->method('getOptions')
            ->willReturn(['price_list_id' => 1, 'importVersion' => 1]);
        $importExportResult->expects($this->once())
            ->method('getType')
            ->willReturn(ProcessorRegistry::TYPE_IMPORT);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn(null);

        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithNullPriceList(): void
    {
        $importExportResult = $this->createMock(ImportExportResult::class);
        $importExportResult->expects($this->any())
            ->method('getOptions')
            ->willReturn(['price_list_id' => 1, 'importVersion' => 1]);
        $importExportResult->expects($this->once())
            ->method('getType')
            ->willReturn(ProcessorRegistry::TYPE_IMPORT);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 1)
            ->willReturn(null);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($entityManager);

        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithValidationTypeSkipsHandler(): void
    {
        $importExportResult = $this->createMock(ImportExportResult::class);
        $importExportResult->expects($this->any())
            ->method('getOptions')
            ->willReturn(['price_list_id' => 1, 'importVersion' => 1]);
        $importExportResult->expects($this->once())
            ->method('getType')
            ->willReturn(ProcessorRegistry::TYPE_IMPORT_VALIDATION);

        $priceList = $this->createMock(PriceList::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 1)
            ->willReturn($priceList);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($entityManager);

        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistSuccessful(): void
    {
        $importExportResult = $this->createMock(ImportExportResult::class);
        $importExportResult->expects($this->any())
            ->method('getOptions')
            ->willReturn(['price_list_id' => 1, 'importVersion' => 1]);
        $importExportResult->expects($this->once())
            ->method('getType')
            ->willReturn(ProcessorRegistry::TYPE_IMPORT);

        $priceList = $this->createMock(PriceList::class);
        $priceList->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 1)
            ->willReturn($priceList);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($entityManager);

        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                GenerateDependentPriceListPricesTopic::getName(),
                [
                    'sourcePriceListId' => 1,
                    'version' => 1
                ]
            );

        $this->listener->postPersist($importExportResult);
    }
}
