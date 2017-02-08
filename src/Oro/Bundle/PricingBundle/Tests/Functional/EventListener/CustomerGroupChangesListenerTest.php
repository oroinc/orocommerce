<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 */
class CustomerGroupChangesListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadPriceListRelations::class
            ]
        );
    }

    /**
     * @param CustomerGroup $group
     */
    protected function sendDeleteCustomerGroupRequest(CustomerGroup $group)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_customer_delete_customer_group', ['id' => $group->getId()])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
    }

    public function testDeleteCustomerGroupWithAssignedCustomer()
    {
        $this->sendDeleteCustomerGroupRequest($this->getReference(LoadGroups::GROUP1));

        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => $this->getReference('customer.level_1.3')->getId()
            ]
        );
    }

    public function testDeleteCustomerGroupWithoutCustomer()
    {
        $this->sendDeleteCustomerGroupRequest($this->getReference(LoadGroups::GROUP3));

        self::assertEmptyMessages(Topics::REBUILD_COMBINED_PRICE_LISTS);
    }
}
