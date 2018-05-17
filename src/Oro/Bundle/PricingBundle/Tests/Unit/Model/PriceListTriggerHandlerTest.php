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
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->handler = new PriceListTriggerHandler(
            $this->triggerFactory,
            $this->messageProducer
        );
    }

    public function testAddTriggersForPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = 1;

        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList, [$product]);
        $this->assertAttributeCount(1, 'triggersData', $this->handler);
    }

    public function testAddTriggersForPriceLists()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = 1;

        $this->handler->addTriggersForPriceLists(Topics::RESOLVE_PRICE_RULES, [$priceList], [$product]);
        $this->assertAttributeCount(1, 'triggersData', $this->handler);
    }

    public function testAddTriggersForPriceListWithoutProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList);
        $this->assertAttributeCount(1, 'triggersData', $this->handler);
    }

    public function testAddTriggersForPriceListsWithoutProduct()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $this->handler->addTriggersForPriceLists(Topics::RESOLVE_PRICE_RULES, [$priceList]);
        $this->assertAttributeCount(1, 'triggersData', $this->handler);
    }

    public function testAddTriggersScheduledTrigger()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 1]);

        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList, [$product]);
        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList, [$product]);

        $this->assertAttributeCount(1, 'triggersData', $this->handler);
    }

    public function testAddTriggersExistingWiderScope()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);

        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList);
        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList, [$product]);

        $this->assertAttributeCount(1, 'triggersData', $this->handler);
    }

    public function testAddTriggersDifferentProducts()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var Product $product1 */
        $product1 = $this->getEntity(Product::class, ['id' => 1]);
        /** @var Product $product2 */
        $product2 = $this->getEntity(Product::class, ['id' => 2]);

        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList, [$product1]);
        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList, [$product2]);

        $this->assertAttributeCount(1, 'triggersData', $this->handler);
        $this->messageProducer->expects($this->exactly(1))
            ->method('send')
            ->withConsecutive(
                [Topics::RESOLVE_PRICE_RULES, null],
                [Topics::RESOLVE_PRICE_RULES, null]
            );

        $this->handler->sendScheduledTriggers();
    }

    public function testIgnoreDisabledPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList->setActive(false);

        $this->triggerFactory->expects($this->never())->method('create');

        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList);
        $this->assertAttributeEmpty('triggersData', $this->handler);
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

        $message1 = [
            PriceListTriggerFactory::PRODUCT => [$priceList1->getId() => []]
        ];
        $message2 = [
            PriceListTriggerFactory::PRODUCT => [$product2->getId() => [$product2->getId()]]
        ];
        $this->triggerFactory->expects($this->exactly(2))
            ->method('createFromIds')
            ->willReturnMap(
                [
                    [[$priceList1->getId() => []], $message1],
                    [[$priceList2->getId() => [$product2->getId()]], $message2],
                ]
            );

        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList1, [$product1]);
        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList1);
        $this->handler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList2, [$product2]);

        $this->assertAttributeCount(1, 'triggersData', $this->handler);

        $this->messageProducer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [Topics::RESOLVE_PRICE_RULES, $message1],
                [Topics::RESOLVE_PRICE_RULES, $message2]
            );

        $this->handler->sendScheduledTriggers();
    }
}
