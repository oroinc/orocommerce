<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ImportExportResultListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Component\Testing\Unit\EntityTrait;

class ImportExportResultListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var PriceRuleLexemeTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $lexemeTriggerHandler;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTriggerHandler;

    /** @var ImportExportResultListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($this->manager);
        $this->lexemeTriggerHandler = $this->createMock(PriceRuleLexemeTriggerHandler::class);
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->listener = new ImportExportResultListener(
            $registry,
            $this->lexemeTriggerHandler,
            $this->priceListTriggerHandler
        );
    }

    public function testPostPersist()
    {
        $importExportResult = new ImportExportResult();
        $importExportResult->setOptions(['price_list_id' => 2]);

        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->manager
            ->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn($priceList);

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

        $this->manager
            ->expects($this->never())
            ->method('find');

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

        $this->manager
            ->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 2)
            ->willReturn(null);

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
}
