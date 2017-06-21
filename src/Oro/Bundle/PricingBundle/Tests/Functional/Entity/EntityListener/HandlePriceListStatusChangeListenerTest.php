<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class HandlePriceListStatusChangeListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadPriceListRelations::class,
        ]);
    }

    public function testActiveFieldChange()
    {
        $this->cleanScheduledRelationMessages();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $priceList->setActive(false);

        static::getContainer()->get('doctrine')->getManager()->flush();

        $this->sendScheduledRelationMessages();

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference('US')->getId(),
            ]
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::ACCOUNT => $this->getReference('customer.level_1_1')->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::WEBSITE => $this->getReference('US')->getId(),
            ]
        );

        static::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::ACCOUNT_GROUP => $this->getReference('customer_group.group1')->getId(),
                PriceListRelationTrigger::WEBSITE => $this->getReference('US')->getId(),
            ]
        );
    }

    public function testNotActiveFieldChange()
    {
        $this->cleanScheduledRelationMessages();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $priceList->setName('Changed');

        static::getContainer()->get('doctrine')->getManager()->flush();

        $this->sendScheduledRelationMessages();

        static::assertMessagesEmpty(Topics::REBUILD_COMBINED_PRICE_LISTS);
    }
}
