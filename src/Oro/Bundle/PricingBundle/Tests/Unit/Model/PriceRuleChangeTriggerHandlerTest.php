<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\TriggersFiller;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Event\PriceRuleChange;
use Oro\Bundle\PricingBundle\Model\DTO\PriceRuleTrigger;
use Oro\Bundle\PricingBundle\Model\DTO\PriceRuleTriggerFactory;
use Oro\Bundle\PricingBundle\Model\PriceRuleChangeTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class PriceRuleChangeTriggerHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceRuleTriggerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerFactory;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageProducer;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    /**
     * @var PriceRuleChangeTriggerHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->triggerFactory = $this->getMockBuilder(PriceRuleTriggerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageProducer = $this->getMock(MessageProducerInterface::class);
        $this->dispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->handler = new PriceRuleChangeTriggerHandler(
            $this->triggerFactory,
            $this->messageProducer,
            $this->dispatcher
        );
    }

    public function testAddTriggersForPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = new Product();
        $trigger = new PriceRuleTrigger($priceList, $product);

        $this->triggerFactory->expects($this->once())
            ->method('create')
            ->with($priceList, $product)
            ->willReturn($trigger);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(PriceRuleChange::NAME, new GenericEvent($trigger));

        $this->handler->addTriggersForPriceList($priceList, $product);
        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersForPriceLists()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = new Product();
        $trigger = new PriceRuleTrigger($priceList, $product);

        $this->triggerFactory->expects($this->once())
            ->method('create')
            ->with($priceList, $product)
            ->willReturn($trigger);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(PriceRuleChange::NAME, new GenericEvent($trigger));

        $this->handler->addTriggersForPriceLists([$priceList], $product);
        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersForPriceListWithoutProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $trigger = new PriceRuleTrigger($priceList);

        $this->triggerFactory->expects($this->once())
            ->method('create')
            ->with($priceList)
            ->willReturn($trigger);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(PriceRuleChange::NAME, new GenericEvent($trigger));

        $this->handler->addTriggersForPriceList($priceList);
        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersForPriceListsWithoutProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $trigger = new PriceRuleTrigger($priceList);

        $this->triggerFactory->expects($this->once())
            ->method('create')
            ->with($priceList)
            ->willReturn($trigger);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(PriceRuleChange::NAME, new GenericEvent($trigger));

        $this->handler->addTriggersForPriceLists([$priceList]);
        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersScheduledTrigger()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $trigger = new PriceRuleTrigger($priceList, $product);

        $this->triggerFactory->expects($this->exactly(2))
            ->method('create')
            ->with($priceList)
            ->willReturn($trigger);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(PriceRuleChange::NAME, new GenericEvent($trigger));

        $this->handler->addTriggersForPriceList($priceList, $product);
        $this->handler->addTriggersForPriceList($priceList, $product);

        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersExistingWiderScope()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $triggerWider = new PriceRuleTrigger($priceList);
        $trigger = new PriceRuleTrigger($priceList, $product);

        $this->triggerFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap(
                [
                    [$priceList, null, $triggerWider],
                    [$priceList, $product, $trigger]
                ]
            );

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->handler->addTriggersForPriceList($priceList);
        $this->handler->addTriggersForPriceList($priceList, $product);

        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersExistingLowerScope()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $triggerWider = new PriceRuleTrigger($priceList);
        $trigger = new PriceRuleTrigger($priceList, $product);

        $this->triggerFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap(
                [
                    [$priceList, null, $triggerWider],
                    [$priceList, $product, $trigger]
                ]
            );

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->handler->addTriggersForPriceList($priceList, $product);
        $this->handler->addTriggersForPriceList($priceList);

        $this->assertAttributeCount(2, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersDifferentProducts()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product1 */
        $product1 = $this->getEntity(Product::class, ['id' => 1]);
        /** @var Product $product2 */
        $product2 = $this->getEntity(Product::class, ['id' => 2]);
        $trigger1 = new PriceRuleTrigger($priceList, $product1);
        $trigger2 = new PriceRuleTrigger($priceList, $product2);

        $this->triggerFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap(
                [
                    [$priceList, $product1, $trigger1],
                    [$priceList, $product2, $trigger2]
                ]
            );

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->handler->addTriggersForPriceList($priceList, $product1);
        $this->handler->addTriggersForPriceList($priceList, $product2);

        $this->assertAttributeCount(2, 'scheduledTriggers', $this->handler);
    }

    public function testIgnoreDisabledPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList->setActive(false);

        $this->triggerFactory->expects($this->never())->method('create');

        $this->handler->addTriggersForPriceList($priceList);
        $this->assertAttributeEmpty('scheduledTriggers', $this->handler);
    }

    public function testSendScheduledTriggers()
    {
        /** @var PriceList $priceList */
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var PriceList $priceList */
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);

        /** @var Product $product1 */
        $product1 = $this->getEntity(Product::class, ['id' => 1]);
        /** @var Product $product2 */
        $product2 = $this->getEntity(Product::class, ['id' => 2]);

        $trigger1 = new PriceRuleTrigger($priceList1);
        $trigger2 = new PriceRuleTrigger($priceList1, $product1);
        $trigger3 = new PriceRuleTrigger($priceList2, $product2);

        $this->triggerFactory->expects($this->exactly(3))
            ->method('create')
            ->willReturnMap(
                [
                    [$priceList1, null, $trigger1],
                    [$priceList1, $product1, $trigger2],
                    [$priceList2, $product2, $trigger3]
                ]
            );

        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch');

        $this->handler->addTriggersForPriceList($priceList1, $product1);
        $this->handler->addTriggersForPriceList($priceList1);
        $this->handler->addTriggersForPriceList($priceList2, $product2);

        $this->assertAttributeCount(3, 'scheduledTriggers', $this->handler);

        $trigger1Data = ['data' => 1];
        $trigger3Data = ['data' => 3];
        $this->triggerFactory->expects($this->exactly(2))
            ->method('triggerToArray')
            ->withConsecutive(
                [$trigger1],
                [$trigger3]
            )
            ->willReturnMap(
                [
                    [$trigger1, $trigger1Data],
                    [$trigger3, $trigger3Data],
                ]
            );
        $this->messageProducer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [Topics::CALCULATE_RULE, $trigger1Data, MessagePriority::NORMAL],
                [Topics::CALCULATE_RULE, $trigger3Data, MessagePriority::NORMAL]
            );

        $this->handler->sendScheduledTriggers();
    }
}
