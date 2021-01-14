<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Command;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\ShoppingListBundle\Command\ClearExpiredCustomerVisitorsCommand;
use Oro\Bundle\ShoppingListBundle\Command\ClearExpiredShoppingListsCommand;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ClearExpiredCustomerVisitorsCommandTest extends WebTestCase
{
    /**
     * @var ObjectManager
     */
    private $em;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadGuestShoppingLists::class
        ]);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(CustomerVisitor::class);
    }

    public function testShouldNotClearExpiredVisitorsWithExistShoppingLists()
    {
        $result = $this->runCommand(ClearExpiredCustomerVisitorsCommand::getDefaultName());
        static::assertStringContainsString('Clear expired customer visitors completed', $result);

        $customerVisitorExpired = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR_EXPIRED);
        $customerVisitorActive = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);

        $this->em->clear(CustomerVisitor::class);
        $this->assertNotNull($this->findVisitor($customerVisitorExpired->getId()));
        $this->assertNotNull($this->findVisitor($customerVisitorActive->getId()));
    }

    public function testShouldClearExpiredVisitorsWithoutShoppingLists()
    {
        $result = $this->runCommand(ClearExpiredShoppingListsCommand::getDefaultName());
        static::assertStringContainsString('Clear expired guest shopping lists completed', $result);

        $result = $this->runCommand(ClearExpiredCustomerVisitorsCommand::getDefaultName());
        static::assertStringContainsString('Clear expired customer visitors completed', $result);

        $customerVisitorExpired = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR_EXPIRED);
        $customerVisitorActive = $this->getReference(LoadCustomerVisitors::CUSTOMER_VISITOR);

        $this->em->clear(CustomerVisitor::class);
        $this->assertNull($this->findVisitor($customerVisitorExpired->getId()));
        $this->assertNotNull($this->findVisitor($customerVisitorActive->getId()));
    }

    /**
     * @param int $id
     * @return CustomerVisitor
     */
    private function findVisitor($id)
    {
        return $this->em->getRepository(CustomerVisitor::class)->find($id);
    }
}
