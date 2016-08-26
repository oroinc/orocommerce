<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\TriggersFiller;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use Oro\Bundle\PricingBundle\Event\PriceRuleChange;
use Oro\Bundle\PricingBundle\TriggersFiller\PriceRuleTriggerFiller;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    protected function setUp()
    {
        $this->extraActionsStorage = $this->getMock(ExtraActionEntityStorageInterface::class);
        $this->dispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->filler = new PriceRuleTriggerFiller($this->extraActionsStorage, $this->dispatcher);
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

        $this->dispatcher->expects($this->once())->method('dispatch')->with(PriceRuleChange::NAME);

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


        $this->dispatcher->expects($this->once())->method('dispatch')->with(PriceRuleChange::NAME);

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

        $this->dispatcher->expects($this->once())->method('dispatch')->with(PriceRuleChange::NAME);

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

        $this->dispatcher->expects($this->once())->method('dispatch')->with(PriceRuleChange::NAME);

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

        $this->dispatcher->expects($this->once())->method('dispatch')->with(PriceRuleChange::NAME);

        $this->filler->addTriggersForPriceList($priceList, $product);
    }

    public function testAddTriggersExistingWiderScope()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $trigger = new PriceRuleChangeTrigger($priceList);

        $this->extraActionsStorage->expects($this->once())
            ->method('getScheduledForInsert')
            ->with(PriceRuleChangeTrigger::class)
            ->willReturn([$trigger]);
        $this->extraActionsStorage->expects($this->never())
            ->method('scheduleForExtraInsert');

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $this->filler->addTriggersForPriceList($priceList, $product);
    }

    public function testAddTriggersLowerScope()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $trigger = new PriceRuleChangeTrigger($priceList, $product);

        $this->extraActionsStorage->expects($this->once())
            ->method('getScheduledForInsert')
            ->with(PriceRuleChangeTrigger::class)
            ->willReturn([$trigger]);

        $expectedTrigger = new PriceRuleChangeTrigger($priceList);
        $this->extraActionsStorage->expects($this->once())
            ->method('scheduleForExtraInsert')
            ->with($expectedTrigger);

        $this->filler->addTriggersForPriceList($priceList);
    }

    public function testAddTriggersDifferentProducts()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product1 */
        $product1 = $this->getEntity(Product::class, ['id' => 1]);
        /** @var Product $product2 */
        $product2 = $this->getEntity(Product::class, ['id' => 2]);
        $trigger = new PriceRuleChangeTrigger($priceList, $product1);

        $this->extraActionsStorage->expects($this->once())
            ->method('getScheduledForInsert')
            ->with(PriceRuleChangeTrigger::class)
            ->willReturn([$trigger]);

        $expectedTrigger = new PriceRuleChangeTrigger($priceList, $product2);
        $this->extraActionsStorage->expects($this->once())
            ->method('scheduleForExtraInsert')
            ->with($expectedTrigger);

        $this->filler->addTriggersForPriceList($priceList, $product2);
    }

    public function testIgnoreDisabledPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList->setActive(false);

        $this->extraActionsStorage->expects($this->never())->method('scheduleForExtraInsert');

        $this->filler->addTriggersForPriceList($priceList);
    }
}
