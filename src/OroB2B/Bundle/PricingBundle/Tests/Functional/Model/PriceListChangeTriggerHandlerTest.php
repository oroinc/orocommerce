<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceListChangeTrigger;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class PriceListChangeTriggerHandlerTest extends WebTestCase
{
    const TOPIC = 'test';
    /**
     * @var PriceListChangeTriggerHandler
     */
    protected $handler;

    /**
     * @var TraceableMessageProducer
     */
    protected $messageProducer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadPriceListRelations::class,
            ]
        );

        $this->handler = $this->getContainer()->get('orob2b_pricing.price_list_change_trigger_handler');
        $this->messageProducer = $this->getContainer()->get('oro_message_queue.message_producer');
    }

    public function testHandleWebsiteChange()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $this->handler->handleWebsiteChange($website);

        $this->assertEquals(
            [
                [
                    'topic' => self::TOPIC,
                    'message' => [
                        PriceListChangeTrigger::WEBSITE => $website->getId(),
                        PriceListChangeTrigger::ACCOUNT => null,
                        PriceListChangeTrigger::ACCOUNT_GROUP => null,
                        PriceListChangeTrigger::FORCE => false,
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
            ],
            $this->messageProducer->getTraces()
        );
    }

    // todo: fix like previous
//    public function testHandleAccountChange()
//    {
//        $this->handler->handleAccountChange($this->account, $this->website);
//
//        $expected = (new PriceListChangeTrigger())
//            ->setWebsite($this->website)
//            ->setAccount($this->account)
//            ->setAccountGroup($this->account->getGroup());
//        $this->assertTriggerWasPersisted($expected);
//    }
//
//    public function testHandleConfigChange()
//    {
//        $this->handler->handleConfigChange();
//        $expected = (new PriceListChangeTrigger());
//        $this->assertTriggerWasPersisted($expected);
//    }
//
//    public function testHandleAccountGroupChange()
//    {
//        $this->handler->handleAccountGroupChange($this->account->getGroup(), $this->website);
//
//        $expected = (new PriceListChangeTrigger())
//            ->setWebsite($this->website)
//            ->setAccountGroup($this->account->getGroup());
//        $this->assertTriggerWasPersisted($expected);
//    }
//
//    public function testHandlePriceListStatusChange()
//    {
//        $priceList = new PriceList();
//        $this->triggersFiller->expects($this->once())
//            ->method('fillTriggersByPriceList')
//            ->with($priceList);
//
//        $this->handler->handlePriceListStatusChange($priceList);
//    }
//
//    public function testHandleFullRebuild()
//    {
//        $this->handler->handleFullRebuild();
//        $expected = (new PriceListChangeTrigger())->setForce(true);
//        $this->assertTriggerWasPersisted($expected);
//
//    }
//
//    public function testHandleAccountGroupRemove()
//    {
//        /** @var PriceListChangeTriggerRepository $triggerRepository */
//        $triggerRepository = $this->getContainer()->get('doctrine')
//            ->getManagerForClass('OroB2BPricingBundle:PriceListChangeTrigger')
//            ->getRepository('OroB2BPricingBundle:PriceListChangeTrigger');
//        $existingTriggers = $triggerRepository->findAll();
//
//        $this->handler->handleAccountGroupRemove($this->account->getGroup());
//
//        // get list of added triggers
//        $addedTriggers = array_filter($triggerRepository->findAll(), function ($trigger) use ($existingTriggers) {
//            return !in_array($trigger, $existingTriggers);
//        });
//
//        $this->assertCount(1, $addedTriggers);
//        $this->assertNotNull(current($addedTriggers)->getAccount());
//
//    }
//
//    /**
//     * @param PriceListChangeTrigger $expected
//     */
//    protected function assertTriggerWasPersisted(PriceListChangeTrigger $expected)
//    {
//        /** @var UnitOfWork $uow */
//        $uow = $this->getContainer()->get('doctrine')->getManager()->getUnitOfWork();
//        $scheduledForInsert = $uow->getScheduledEntityInsertions();
//
//        $this->assertCount(1, $scheduledForInsert);
//        $this->assertEquals($expected, current($scheduledForInsert));
//    }
}
