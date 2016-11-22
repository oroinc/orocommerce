<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\EventListener;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
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

    public function testDeleteAccountGroupWithAssignedAccount()
    {
        $this->sendDeleteAccountGroupRequest($this->getReference(LoadGroups::GROUP1));

        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => $this->getReference('account.level_1.3')->getId()
            ]
        );
    }

    public function testDeleteAccountGroupWithoutAccount()
    {
        $this->sendDeleteAccountGroupRequest($this->getReference(LoadGroups::GROUP3));

        self::assertEmptyMessages(Topics::REBUILD_COMBINED_PRICE_LISTS);
    }
}
