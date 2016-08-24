<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;

/**
 * @dbIsolation
 */
class PriceListRelationTriggerHandlerTest extends WebTestCase
{
    /**
     * @var PriceListRelationTriggerHandler
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

        $this->handler = $this->getContainer()->get('orob2b_pricing.price_list_relation_trigger_handler');
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
                    'topic' => Topics::REBUILD_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $website->getId(),
                        PriceListRelationTrigger::ACCOUNT => null,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::FORCE => false,
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
                    'topic' => Topics::REBUILD_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $website->getId(),
                        PriceListRelationTrigger::ACCOUNT => $account->getId(),
                        PriceListRelationTrigger::ACCOUNT_GROUP => $account->getGroup()->getId(),
                        PriceListRelationTrigger::FORCE => false,
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
                    'topic' => Topics::REBUILD_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => null,
                        PriceListRelationTrigger::ACCOUNT => null,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::FORCE => false,
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
                    'topic' => Topics::REBUILD_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $website->getId(),
                        PriceListRelationTrigger::ACCOUNT => null,
                        PriceListRelationTrigger::ACCOUNT_GROUP => $accountGroup->getId(),
                        PriceListRelationTrigger::FORCE => false,
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
                    'topic' => Topics::REBUILD_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        PriceListRelationTrigger::ACCOUNT =>  $this->getReference('account.level_1.3')->getId(),
                        PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference(LoadGroups::GROUP1)->getId(),
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
                [
                    'topic' => Topics::REBUILD_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference(LoadGroups::GROUP1)->getId(),
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
                [
                    'topic' => Topics::REBUILD_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
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
                    'topic' => Topics::REBUILD_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => null,
                        PriceListRelationTrigger::ACCOUNT => null,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::FORCE => true,
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
                    'topic' => Topics::REBUILD_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        PriceListRelationTrigger::ACCOUNT => $this->getReference('account.level_1.3')->getId(),
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
            ],
            $this->messageProducer->getTraces()
        );
    }
}
