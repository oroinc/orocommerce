<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
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

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    private $registry;

    /**
     * @var PriceRuleLexemeTriggerHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $lexemeTriggerHandler;

    /**
     * @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceListTriggerHandler;

    /**
     * @var ShardManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shardManager;

    /**
     * @var ImportExportResultListener
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->lexemeTriggerHandler = $this->createMock(PriceRuleLexemeTriggerHandler::class);
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->listener = new ImportExportResultListener(
            $this->registry,
            $this->lexemeTriggerHandler,
            $this->priceListTriggerHandler
        );
        $this->listener->setShardManager($this->shardManager);
    }

    public function testPostPersist()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);

        $lexemes = [new PriceRuleLexeme()];
        $this->lexemeTriggerHandler
            ->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, ['prices'], $priceList->getId())
            ->willReturn($lexemes);

        $this->lexemeTriggerHandler
            ->expects($this->once())
            ->method('addTriggersByLexemes')
            ->with($lexemes);

        $this->priceListTriggerHandler
            ->expects($this->once())
            ->method('addTriggerForPriceList')
            ->with('oro_pricing.price_lists.cpl.resolve_prices', $priceList);

        $this->priceListTriggerHandler
            ->expects($this->once())
            ->method('sendScheduledTriggers');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithoutOption()
    {
        $importExportResult = new ImportExportResult();

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->never())
            ->method('find');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);

        $this->lexemeTriggerHandler
            ->expects($this->never())
            ->method('findEntityLexemes');

        $this->lexemeTriggerHandler
            ->expects($this->never())
            ->method('addTriggersByLexemes');

        $this->priceListTriggerHandler
            ->expects($this->never())
            ->method('addTriggerForPriceList');

        $this->priceListTriggerHandler
            ->expects($this->never())
            ->method('sendScheduledTriggers');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithoutPriceList()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn(null);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($manager);

        $this->lexemeTriggerHandler
            ->expects($this->never())
            ->method('findEntityLexemes');

        $this->lexemeTriggerHandler
            ->expects($this->never())
            ->method('addTriggersByLexemes');

        $this->priceListTriggerHandler
            ->expects($this->never())
            ->method('addTriggerForPriceList');

        $this->priceListTriggerHandler
            ->expects($this->never())
            ->method('sendScheduledTriggers');

        $this->listener->postPersist($importExportResult);
    }

    public function testPostPersistWithPriceListAndVersion()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2, 'importVersion' => 1]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);
        $products1 = [1, 3, 5];
        $products2 = [7];

        $lexemes = [new PriceRuleLexeme()];
        $this->lexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, ['prices'], $priceList->getId())
            ->willReturn($lexemes);

        $repo = $this->createMock(ProductPriceRepository::class);
        $repo->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->with($this->shardManager, $priceList, 1, PriceListTriggerHandler::BATCH_SIZE)
            ->willReturn([$products1, $products2]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repo);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->lexemeTriggerHandler->expects($this->exactly(2))
            ->method('addTriggersByLexemes')
            ->withConsecutive(
                [$lexemes, $products1],
                [$lexemes, $products2]
            );

        $this->priceListTriggerHandler->expects($this->exactly(2))
            ->method('addTriggerForPriceList')
            ->withConsecutive(
                ['oro_pricing.price_lists.cpl.resolve_prices', $priceList, $products1],
                ['oro_pricing.price_lists.cpl.resolve_prices', $priceList, $products2]
            );

        $this->priceListTriggerHandler->expects($this->exactly(2))
            ->method('sendScheduledTriggers');

        $this->listener->postPersist($importExportResult);
    }
}
