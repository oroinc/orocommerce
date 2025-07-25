<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Command;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ClearExpiredShoppingListsCommandTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadGuestShoppingLists::class
        ]);
    }

    public function testShouldClearExpiredShoppingLists()
    {
        $result = $this->runCommand('oro:cron:shopping-list:clear-expired');

        static::assertStringContainsString('Clear expired guest shopping lists completed', $result);

        $customerVisitor = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR_EXPIRED);
        $this->assertEmpty($customerVisitor->getShoppingLists());

        /** @var CustomerVisitor $customerVisitorNoExpired */
        $customerVisitorNoExpired = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);
        $this->assertCount(1, $customerVisitorNoExpired->getShoppingLists());
    }
}
