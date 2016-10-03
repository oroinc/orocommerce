<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\EventListener;

use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountGroupChangesListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader(), true);
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadPriceListRelations::class
            ],
            true
        );
    }

    /**
     * @param AccountGroup $group
     */
    protected function sendDeleteAccountGroupRequest(AccountGroup $group)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_account_delete_account_group', ['id' => $group->getId()])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
    }

    /**
     * @todo: fix test name
     */
    public function testDeleteGroup1()
    {
        $this->sendDeleteAccountGroupRequest($this->getReference('account_group.group1'));

        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => $this->getReference('account.level_1.3')->getId()
            ]
        );
    }

    /**
     * @todo: fix test name
     */
    public function testDeleteGroup2()
    {
        $this->sendDeleteAccountGroupRequest($this->getReference('account_group.group2'));

        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => $this->getReference('account.level_1.2')->getId()
            ]
        );
    }

    /**
     * @todo: fix test name
     */
    public function testDeleteGroup3()
    {
        $this->sendDeleteAccountGroupRequest($this->getReference('account_group.group3'));

        self::assertEmptyMessages(Topics::REBUILD_COMBINED_PRICE_LISTS);
    }
}
