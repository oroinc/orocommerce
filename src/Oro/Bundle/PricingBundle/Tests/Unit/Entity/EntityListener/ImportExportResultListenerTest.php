<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByVersionedPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveVersionedFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ImportExportResultListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportExportResultListenerTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry|MockObject $doctrine;
    private PriceRuleLexemeTriggerHandler|MockObject $lexemeTriggerHandler;
    private ShardManager|MockObject $shardManager;
    private FeatureChecker|MockObject $featureChecker;
    private MessageProducerInterface|MockObject $producer;
    private PriceListRelationTriggerHandler|MockObject $priceListRelationTriggerHandler;

    private ImportExportResultListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->lexemeTriggerHandler = $this->createMock(PriceRuleLexemeTriggerHandler::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->priceListRelationTriggerHandler = $this->createMock(PriceListRelationTriggerHandler::class);

        $this->listener = new ImportExportResultListener(
            $this->doctrine,
            $this->lexemeTriggerHandler,
            $this->shardManager,
            $this->producer,
            $this->priceListRelationTriggerHandler
        );
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_price_lists_combined');
    }

    public function testPostPersistFeatureDisabled()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2, 'importVersion' => 1]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(false);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository
            ->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->willReturn([[1]]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);

        $this->doctrine
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);
        $this->doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($productPriceRepository);

        $lexemes = [new PriceRuleLexeme()];
        $this->lexemeTriggerHandler
            ->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, ['prices'], $priceList->getId())
            ->willReturn($lexemes);
        $this->lexemeTriggerHandler
            ->expects($this->once())
            ->method('processLexemes')
            ->with($lexemes);

        $this->priceListRelationTriggerHandler->expects($this->never())
            ->method('handlePriceListStatusChange');

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(ResolveVersionedFlatPriceTopic::getName(), ['version' => 1, 'priceLists' => [2]]);

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersist()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2, 'importVersion' => 2]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository
            ->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->willReturn([[1]]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);

        $this->doctrine
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);
        $this->doctrine
            ->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($productPriceRepository);

        $lexemes = [new PriceRuleLexeme()];
        $this->lexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, ['prices'], $priceList->getId())
            ->willReturn($lexemes);

        $this->lexemeTriggerHandler->expects($this->once())
            ->method('processLexemes')
            ->with($lexemes);

        $this->priceListRelationTriggerHandler->expects($this->never())
            ->method('handlePriceListStatusChange');

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(ResolveCombinedPriceByVersionedPriceListTopic::getName(), ['version' => 2, 'priceLists' => [2]]);

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistInactivePriceList()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2, 'importVersion' => 2]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList->setActive(false);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository
            ->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->willReturn([[1]]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);

        $this->doctrine
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);
        $this->doctrine
            ->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($productPriceRepository);

        $lexemes = [new PriceRuleLexeme()];
        $this->lexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, ['prices'], $priceList->getId())
            ->willReturn($lexemes);

        $this->lexemeTriggerHandler->expects($this->once())
            ->method('processLexemes')
            ->with($lexemes);

        $this->priceListRelationTriggerHandler->expects($this->never())
            ->method('handlePriceListStatusChange');

        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistAllVersionedPricesNew()
    {
        $version = 42;
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2, 'importVersion' => $version]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList->setActive(true);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $productPriceRepository = $this->createMock(ProductPriceRepository::class);
        $productPriceRepository
            ->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->willReturn([[1]]);
        $productPriceRepository->expects($this->once())
            ->method('areAllVersionedPricesNewInPriceList')
            ->with($this->shardManager, $priceList, $version)
            ->willReturn(true);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);

        $this->doctrine
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);
        $this->doctrine
            ->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($productPriceRepository);

        $lexemes = [new PriceRuleLexeme()];
        $this->lexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, ['prices'], $priceList->getId())
            ->willReturn($lexemes);

        $this->lexemeTriggerHandler->expects($this->once())
            ->method('processLexemes')
            ->with($lexemes);

        $this->priceListRelationTriggerHandler->expects($this->once())
            ->method('handlePriceListStatusChange')
            ->with($priceList);

        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->postPersist($importExportResult);
    }

    /**
     * @dataProvider importExportResultProvider
     */
    public function testPostPersistWithoutOption(ImportExportResult $importExportResult): void
    {
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->never())
            ->method('find');

        $this->lexemeTriggerHandler
            ->expects($this->never())
            ->method('findEntityLexemes');

        $this->priceListRelationTriggerHandler->expects($this->never())
            ->method('handlePriceListStatusChange');

        $this->producer
            ->expects($this->never())
            ->method('send');

        $this->listener->postPersist($importExportResult);
    }

    public function importExportResultProvider(): \Generator
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions([]);
        yield [$importExportResult];

        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => [1]]);
        yield [$importExportResult];

        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['version' => 1]);
        yield [$importExportResult];
    }

    public function testPostPersistWithPriceListAndVersion()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2, 'importVersion' => 1]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);
        $products1 = [1, 3, 5];
        $products2 = [7];

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $lexemes = [new PriceRuleLexeme()];
        $this->lexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, ['prices'], $priceList->getId())
            ->willReturn($lexemes);

        $repo = $this->createMock(ProductPriceRepository::class);
        $repo->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->with($this->shardManager, $priceList->getId(), 1)
            ->willReturn([$products1, $products2]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);
        $this->doctrine->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repo);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->lexemeTriggerHandler->expects($this->exactly(2))
            ->method('processLexemes')
            ->withConsecutive(
                [$lexemes, $products1],
                [$lexemes, $products2]
            );

        $this->priceListRelationTriggerHandler->expects($this->never())
            ->method('handlePriceListStatusChange');

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(ResolveCombinedPriceByVersionedPriceListTopic::getName(), ['version' => 1, 'priceLists' => [2]]);

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithImportValidation()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setType(ProcessorRegistry::TYPE_IMPORT_VALIDATION);
        $importExportResult->setOptions(['price_list_id' => 2, 'importVersion' => 2]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->priceListRelationTriggerHandler->expects($this->never())
            ->method('handlePriceListStatusChange');

        $this->producer
            ->expects($this->never())
            ->method('send');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->willReturn($priceList);

        $this->doctrine
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->listener->postPersist($importExportResult);
    }
}
