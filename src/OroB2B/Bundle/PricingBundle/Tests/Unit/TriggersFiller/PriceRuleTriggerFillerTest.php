<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\TriggersFiller;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\TriggersFiller\PriceRuleTriggerFiller;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceRuleTriggerFillerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ExtraActionEntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $extraActionsStorage;

    /**
     * @var PriceRuleTriggerFiller
     */
    protected $filler;

    protected function setUp()
    {
        $this->extraActionsStorage = $this->getMock(ExtraActionEntityStorageInterface::class);
        $this->filler = new PriceRuleTriggerFiller($this->extraActionsStorage);
    }

    public function testAddTriggersForPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = new Product();
        $trigger = new PriceRuleChangeTrigger($priceList, $product);

        $this->extraActionsStorage->expects($this->once())
            ->method('getScheduledForInsert')
            ->with(PriceRuleChangeTrigger::class)
            ->willReturn([]);
        $this->extraActionsStorage->expects($this->once())
            ->method('scheduleForExtraInsert')
            ->with($trigger);

        $this->filler->addTriggersForPriceList($priceList, $product);
    }

    public function testAddTriggersForPriceLists()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = new Product();
        $trigger = new PriceRuleChangeTrigger($priceList, $product);

        $this->extraActionsStorage->expects($this->once())
            ->method('getScheduledForInsert')
            ->with(PriceRuleChangeTrigger::class)
            ->willReturn([]);
        $this->extraActionsStorage->expects($this->once())
            ->method('scheduleForExtraInsert')
            ->with($trigger);

        $this->filler->addTriggersForPriceLists([$priceList], $product);
    }

    public function testAddTriggersForPriceListWithoutProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $trigger = new PriceRuleChangeTrigger($priceList);

        $this->extraActionsStorage->expects($this->once())
            ->method('getScheduledForInsert')
            ->with(PriceRuleChangeTrigger::class)
            ->willReturn([]);
        $this->extraActionsStorage->expects($this->once())
            ->method('scheduleForExtraInsert')
            ->with($trigger);

        $this->filler->addTriggersForPriceList($priceList);
    }

    public function testAddTriggersForPriceListsWithoutProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $trigger = new PriceRuleChangeTrigger($priceList);

        $this->extraActionsStorage->expects($this->once())
            ->method('getScheduledForInsert')
            ->with(PriceRuleChangeTrigger::class)
            ->willReturn([]);
        $this->extraActionsStorage->expects($this->once())
            ->method('scheduleForExtraInsert')
            ->with($trigger);

        $this->filler->addTriggersForPriceLists([$priceList]);
    }

    public function testAddTriggersScheduledTrigger()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = new Product();
        $trigger = new PriceRuleChangeTrigger($priceList, $product);

        $this->extraActionsStorage->expects($this->once())
            ->method('getScheduledForInsert')
            ->with(PriceRuleChangeTrigger::class)
            ->willReturn([$trigger]);
        $this->extraActionsStorage->expects($this->never())
            ->method('scheduleForExtraInsert');

        $this->filler->addTriggersForPriceList($priceList, $product);
    }
}
