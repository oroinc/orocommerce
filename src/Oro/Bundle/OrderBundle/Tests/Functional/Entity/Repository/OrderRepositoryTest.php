<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Entity\Respository;

use Doctrine\Common\Collections\AbstractLazyCollection;
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
    /** @var OrderRepository */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganizations::class,
            LoadOrders::class,
        ]);

        $this->repository = $this->getRepository();
    }

    public function testHasRecordsWithRemovingCurrencies()
    {
        /** @var User $user */
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);
        $this->assertNotNull($user);

        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganizations::ORGANIZATION_1);
        $this->assertNotNull($organization);

        $this->assertTrue($this->repository->hasRecordsWithRemovingCurrencies(['USD']));
        $this->assertTrue($this->repository->hasRecordsWithRemovingCurrencies(['EUR']));
        $this->assertFalse($this->repository->hasRecordsWithRemovingCurrencies(['UAH']));
        $this->assertTrue($this->repository->hasRecordsWithRemovingCurrencies(['EUR'], $user->getOrganization()));
        $this->assertFalse($this->repository->hasRecordsWithRemovingCurrencies(['USD'], $organization));
    }

    public function testGetOrderWithRelations()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $orderWithRelations = $this->repository->getOrderWithRelations($order->getId());

        /** @var AbstractLazyCollection $lineItems */
        $lineItems = $orderWithRelations->getLineItems();

        /** @var AbstractLazyCollection $discounts */
        $discounts = $orderWithRelations->getDiscounts();

        $this->assertTrue($lineItems->isInitialized());
        $this->assertTrue($discounts->isInitialized());
    }

    /**
     * @return OrderRepository
     */
    private function getRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Order::class)
            ->getRepository(Order::class);
    }
}
