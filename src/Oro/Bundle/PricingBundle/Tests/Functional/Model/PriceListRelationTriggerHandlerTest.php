<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

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
     * @var MessageCollector
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

        $this->handler = $this->getContainer()->get('oro_pricing.price_list_relation_trigger_handler');
        $this->messageProducer = $this->getContainer()->get('oro_message_queue.message_producer');
        $this->messageProducer->clear();
        $this->messageProducer->enable();
    }

    public function testHandleWebsiteChange()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $this->handler->handleWebsiteChange($website);
        $this->handler->sendScheduledTriggers();

        $this->assertEquals(
            [
                [
                    'topic' => Topics::REBUILD_COMBINED_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $website->getId(),
                        PriceListRelationTrigger::ACCOUNT => null,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::FORCE => false,
                    ],
                ],
            ],
            $this->messageProducer->getSentMessages()
        );
    }

    public function testHandleAccountChange()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var Account $account */
        $account = $this->getReference('account.level_1');

        $this->handler->handleAccountChange($account, $website);
        $this->handler->sendScheduledTriggers();

        $this->assertEquals(
            [
                [
                    'topic' => Topics::REBUILD_COMBINED_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $website->getId(),
                        PriceListRelationTrigger::ACCOUNT => $account->getId(),
                        PriceListRelationTrigger::ACCOUNT_GROUP => $account->getGroup()->getId(),
                        PriceListRelationTrigger::FORCE => false,
                    ],
                ],
            ],
            $this->messageProducer->getSentMessages()
        );
    }

    public function testHandleConfigChange()
    {
        $this->handler->handleConfigChange();
        $this->handler->sendScheduledTriggers();

        $this->assertEquals(
            [
                [
                    'topic' => Topics::REBUILD_COMBINED_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => null,
                        PriceListRelationTrigger::ACCOUNT => null,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::FORCE => false,
                    ],
                ],
            ],
            $this->messageProducer->getSentMessages()
        );
    }

    public function testHandleAccountGroupChange()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference(LoadGroups::GROUP1);
        $this->handler->handleAccountGroupChange($accountGroup, $website);
        $this->handler->sendScheduledTriggers();

        $this->assertEquals(
            [
                [
                    'topic' => Topics::REBUILD_COMBINED_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $website->getId(),
                        PriceListRelationTrigger::ACCOUNT => null,
                        PriceListRelationTrigger::ACCOUNT_GROUP => $accountGroup->getId(),
                        PriceListRelationTrigger::FORCE => false,
                    ],
                ],
            ],
            $this->messageProducer->getSentMessages()
        );
    }

    public function testHandlePriceListStatusChange()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_6');
        $this->handler->handlePriceListStatusChange($priceList);
        $this->handler->sendScheduledTriggers();

        $this->assertEquals(
            [
                [
                    'topic' => Topics::REBUILD_COMBINED_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        PriceListRelationTrigger::ACCOUNT =>  $this->getReference('account.level_1.3')->getId(),
                        PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference(LoadGroups::GROUP1)->getId(),
                    ],
                ],
                [
                    'topic' => Topics::REBUILD_COMBINED_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference(LoadGroups::GROUP1)->getId(),
                    ],
                ],
                [
                    'topic' => Topics::REBUILD_COMBINED_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                    ],
                ],
            ],
            $this->messageProducer->getSentMessages()
        );
    }

    public function testHandleFullRebuild()
    {
        $this->handler->handleFullRebuild();
        $this->handler->sendScheduledTriggers();

        $this->assertEquals(
            [
                [
                    'topic' => Topics::REBUILD_COMBINED_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => null,
                        PriceListRelationTrigger::ACCOUNT => null,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::FORCE => true,
                    ],
                ],
            ],
            $this->messageProducer->getSentMessages()
        );
    }

    public function testHandleAccountGroupRemove()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference(LoadGroups::GROUP1);
        $this->handler->handleAccountGroupRemove($accountGroup);
        $this->handler->sendScheduledTriggers();

        $this->assertEquals(
            [
                [
                    'topic' => Topics::REBUILD_COMBINED_PRICE_LISTS,
                    'message' => [
                        PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        PriceListRelationTrigger::ACCOUNT => $this->getReference('account.level_1.3')->getId(),
                    ],
                ],
            ],
            $this->messageProducer->getSentMessages()
        );
    }
}
