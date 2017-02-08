<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Entity\Respository;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrganizations;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class OrderRepositoryTest extends WebTestCase
{
    /**
     * @var OrderRepository
     */
    protected $orderRepo;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadOrders::class,
            LoadOrganizations::class,
        ]);

        $this->orderRepo = $this->client->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(Order::class);
    }

    public function testHasRecordsWithRemovingCurrencies()
    {
        /** @var User $user */
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);

        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganizations::ORGANIZATION_1);

        $this->assertTrue($this->orderRepo->hasRecordsWithRemovingCurrencies(['USD']));
        $this->assertTrue($this->orderRepo->hasRecordsWithRemovingCurrencies(['EUR']));
        $this->assertFalse($this->orderRepo->hasRecordsWithRemovingCurrencies(['UAH']));
        $this->assertTrue($this->orderRepo->hasRecordsWithRemovingCurrencies(['EUR'], $user->getOrganization()));
        $this->assertFalse($this->orderRepo->hasRecordsWithRemovingCurrencies(['USD'], $organization));
    }
}
