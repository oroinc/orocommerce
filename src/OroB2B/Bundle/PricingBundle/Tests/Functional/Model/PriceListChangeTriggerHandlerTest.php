<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
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
        $this->messageProducer->clearTraces();
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

    public function testHandleAccountChange()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var Account $account */
        $account = $this->getReference('account.level_1');

        $this->handler->handleAccountChange($account, $website);

        $this->assertEquals(
            [
                [
                    'topic' => self::TOPIC,
                    'message' => [
                        PriceListChangeTrigger::WEBSITE => $website->getId(),
                        PriceListChangeTrigger::ACCOUNT => $account->getId(),
                        PriceListChangeTrigger::ACCOUNT_GROUP => $account->getGroup()->getId(),
                        PriceListChangeTrigger::FORCE => false,
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
            ],
            $this->messageProducer->getTraces()
        );
    }

    public function testHandleConfigChange()
    {
        $this->handler->handleConfigChange();

        $this->assertEquals(
            [
                [
                    'topic' => self::TOPIC,
                    'message' => [
                        PriceListChangeTrigger::WEBSITE => null,
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

    public function testHandleAccountGroupChange()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference(LoadGroups::GROUP1);
        $this->handler->handleAccountGroupChange($accountGroup, $website);

        $this->assertEquals(
            [
                [
                    'topic' => self::TOPIC,
                    'message' => [
                        PriceListChangeTrigger::WEBSITE => $website->getId(),
                        PriceListChangeTrigger::ACCOUNT => null,
                        PriceListChangeTrigger::ACCOUNT_GROUP => $accountGroup->getId(),
                        PriceListChangeTrigger::FORCE => false,
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
            ],
            $this->messageProducer->getTraces()
        );
    }

    public function testHandlePriceListStatusChange()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_6');
        $this->handler->handlePriceListStatusChange($priceList);

        $this->assertEquals(
            [
                [
                    'topic' => self::TOPIC,
                    'message' => [
                        PriceListChangeTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        PriceListChangeTrigger::ACCOUNT =>  $this->getReference('account.level_1.3')->getId(),
                        PriceListChangeTrigger::ACCOUNT_GROUP => $this->getReference(LoadGroups::GROUP1)->getId(),
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
                [
                    'topic' => self::TOPIC,
                    'message' => [
                        PriceListChangeTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        PriceListChangeTrigger::ACCOUNT_GROUP => $this->getReference(LoadGroups::GROUP1)->getId(),
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
                [
                    'topic' => self::TOPIC,
                    'message' => [
                        PriceListChangeTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
            ],
            $this->messageProducer->getTraces()
        );
    }

    public function testHandleFullRebuild()
    {
        $this->handler->handleFullRebuild();

        $this->assertEquals(
            [
                [
                    'topic' => self::TOPIC,
                    'message' => [
                        PriceListChangeTrigger::WEBSITE => null,
                        PriceListChangeTrigger::ACCOUNT => null,
                        PriceListChangeTrigger::ACCOUNT_GROUP => null,
                        PriceListChangeTrigger::FORCE => true,
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
            ],
            $this->messageProducer->getTraces()
        );
    }

    public function testHandleAccountGroupRemove()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference(LoadGroups::GROUP1);
        $this->handler->handleAccountGroupRemove($accountGroup);

        $this->assertEquals(
            [
                [
                    'topic' => self::TOPIC,
                    'message' => [
                        PriceListChangeTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        PriceListChangeTrigger::ACCOUNT => $this->getReference('account.level_1.3')->getId(),
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
            ],
            $this->messageProducer->getTraces()
        );
    }
}
