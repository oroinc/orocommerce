<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ImportExportResultListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\Unit\EntityTrait;

class ImportExportResultListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PriceRuleLexemeTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $lexemeTriggerHandler;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTriggerHandler;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var ImportExportResultListener */
    private $listener;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $featureChecker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->lexemeTriggerHandler = $this->createMock(PriceRuleLexemeTriggerHandler::class);
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ImportExportResultListener(
            $this->doctrine,
            $this->lexemeTriggerHandler,
            $this->priceListTriggerHandler,
            $this->shardManager
        );
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_price_lists_combined');
    }

    public function testPostPersistFeatureDisabled()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(false);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);

        $lexemes = [new PriceRuleLexeme()];
        $this->lexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, ['prices'], $priceList->getId())
            ->willReturn($lexemes);

        $this->lexemeTriggerHandler->expects($this->once())
            ->method('processLexemes')
            ->with($lexemes);

        $this->priceListTriggerHandler->expects($this->never())
            ->method('handlePriceListTopic')
            ->with(Topics::RESOLVE_COMBINED_PRICES, $priceList);

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersist()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);

        $lexemes = [new PriceRuleLexeme()];
        $this->lexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, ['prices'], $priceList->getId())
            ->willReturn($lexemes);

        $this->lexemeTriggerHandler->expects($this->once())
            ->method('processLexemes')
            ->with($lexemes);

        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(Topics::RESOLVE_COMBINED_PRICES, $priceList);

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithoutOption()
    {
        $importExportResult = new ImportExportResult();

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->never())
            ->method('find');
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);

        $this->lexemeTriggerHandler->expects($this->never())
            ->method('findEntityLexemes');

        $this->lexemeTriggerHandler->expects($this->never())
            ->method('processLexemes');

        $this->priceListTriggerHandler->expects($this->never())
            ->method('handlePriceListTopic');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithoutPriceList()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2]);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn(null);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);

        $this->lexemeTriggerHandler->expects($this->never())
            ->method('findEntityLexemes');

        $this->lexemeTriggerHandler->expects($this->never())
            ->method('processLexemes');

        $this->priceListTriggerHandler->expects($this->never())
            ->method('handlePriceListTopic');

        $this->listener->postPersist($importExportResult);
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
            ->with($this->shardManager, $priceList, 1)
            ->willReturn([$products1, $products2]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);
        $this->doctrine->expects($this->once())
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

        $this->priceListTriggerHandler->expects($this->exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [Topics::RESOLVE_COMBINED_PRICES, $priceList, $products1],
                [Topics::RESOLVE_COMBINED_PRICES, $priceList, $products2]
            );

        $this->listener->postPersist($importExportResult);
    }
}
