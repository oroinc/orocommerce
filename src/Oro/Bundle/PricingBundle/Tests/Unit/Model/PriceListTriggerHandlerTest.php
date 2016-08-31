<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceListTriggerHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceListTriggerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerFactory;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageProducer;

    /**
     * @var PriceListTriggerHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->triggerFactory = $this->getMockBuilder(PriceListTriggerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageProducer = $this->getMock(MessageProducerInterface::class);
        $this->handler = new PriceListTriggerHandler(
            $this->triggerFactory,
            $this->messageProducer
        );
    }

    public function testAddTriggersForPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = new Product();
        $trigger = new PriceListTrigger($priceList, $product);

        $this->triggerFactory->expects($this->once())
            ->method('create')
            ->with($priceList, $product)
            ->willReturn($trigger);

        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList, $product);
        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersForPriceLists()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = new Product();
        $trigger = new PriceListTrigger($priceList, $product);

        $this->triggerFactory->expects($this->once())
            ->method('create')
            ->with($priceList, $product)
            ->willReturn($trigger);

        $this->handler->addTriggersForPriceLists(Topics::CALCULATE_RULE, [$priceList], $product);
        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersForPriceListWithoutProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $trigger = new PriceListTrigger($priceList);

        $this->triggerFactory->expects($this->once())
            ->method('create')
            ->with($priceList)
            ->willReturn($trigger);

        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList);
        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersForPriceListsWithoutProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $trigger = new PriceListTrigger($priceList);

        $this->triggerFactory->expects($this->once())
            ->method('create')
            ->with($priceList)
            ->willReturn($trigger);

        $this->handler->addTriggersForPriceLists(Topics::CALCULATE_RULE, [$priceList]);
        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersScheduledTrigger()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $trigger = new PriceListTrigger($priceList, $product);

        $this->triggerFactory->expects($this->exactly(2))
            ->method('create')
            ->with($priceList)
            ->willReturn($trigger);

        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList, $product);
        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList, $product);

        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersExistingWiderScope()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $triggerWider = new PriceListTrigger($priceList);
        $trigger = new PriceListTrigger($priceList, $product);

        $this->triggerFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap(
                [
                    [$priceList, null, $triggerWider],
                    [$priceList, $product, $trigger]
                ]
            );

        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList);
        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList, $product);

        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersExistingLowerScope()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $triggerWider = new PriceListTrigger($priceList);
        $trigger = new PriceListTrigger($priceList, $product);

        $this->triggerFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap(
                [
                    [$priceList, null, $triggerWider],
                    [$priceList, $product, $trigger]
                ]
            );

        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList, $product);
        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList);

        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
    }

    public function testAddTriggersDifferentProducts()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product1 */
        $product1 = $this->getEntity(Product::class, ['id' => 1]);
        /** @var Product $product2 */
        $product2 = $this->getEntity(Product::class, ['id' => 2]);
        $trigger1 = new PriceListTrigger($priceList, $product1);
        $trigger2 = new PriceListTrigger($priceList, $product2);

        $this->triggerFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap(
                [
                    [$priceList, $product1, $trigger1],
                    [$priceList, $product2, $trigger2]
                ]
            );

        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList, $product1);
        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList, $product2);

        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);
        $this->messageProducer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [Topics::CALCULATE_RULE, null],
                [Topics::CALCULATE_RULE, null]
            );

        $this->handler->sendScheduledTriggers();
    }

    public function testIgnoreDisabledPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList->setActive(false);

        $this->triggerFactory->expects($this->never())->method('create');

        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList);
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

        $trigger1 = new PriceListTrigger($priceList1);
        $trigger2 = new PriceListTrigger($priceList1, $product1);
        $trigger3 = new PriceListTrigger($priceList2, $product2);

        $this->triggerFactory->expects($this->exactly(3))
            ->method('create')
            ->willReturnMap(
                [
                    [$priceList1, null, $trigger1],
                    [$priceList1, $product1, $trigger2],
                    [$priceList2, $product2, $trigger3]
                ]
            );

        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList1, $product1);
        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList1);
        $this->handler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList2, $product2);

        $this->assertAttributeCount(1, 'scheduledTriggers', $this->handler);

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
                [Topics::CALCULATE_RULE, $trigger1Data],
                [Topics::CALCULATE_RULE, $trigger3Data]
            );

        $this->handler->sendScheduledTriggers();
    }
}
