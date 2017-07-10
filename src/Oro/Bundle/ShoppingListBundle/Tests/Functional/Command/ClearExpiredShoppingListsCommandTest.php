<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Command;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ClearExpiredShoppingListsCommandTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadGuestShoppingLists::class
        ]);
    }

    public function testShouldSendSyncIntegrationWithoutAnyAdditionalOptions()
    {
        $result = $this->runCommand('oro:cron:shopping-list:clear-expired');

        $this->assertContains('Clear expired guest shopping lists completed', $result);

        $customerVisitor = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR_EXPIRED);
        $this->assertEmpty($customerVisitor->getShoppingLists());
    }
}
