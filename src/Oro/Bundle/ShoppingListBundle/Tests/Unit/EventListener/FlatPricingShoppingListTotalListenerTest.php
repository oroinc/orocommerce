<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerGroupRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\MassStorageUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\WebsiteRelationUpdateEvent;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\EventListener\FlatPricingShoppingListTotalListener;
use PHPUnit\Framework\MockObject\MockObject;

class FlatPricingShoppingListTotalListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|MockObject
     */
    private $registry;

    /**
     * @var FlatPricingShoppingListTotalListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->listener = new FlatPricingShoppingListTotalListener($this->registry);
    }

    public function testOnPriceListUpdate()
    {
        $event = new MassStorageUpdateEvent([1]);
        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('invalidateByPriceList')
            ->with([1]);

        $this->listener->onPriceListUpdate($event);
    }

    public function testOnCustomerPriceListUpdate()
    {
        $event = new CustomerRelationUpdateEvent([['websiteId' => 1, 'customers' => [2, 3]]]);
        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('invalidateByCustomers')
            ->with([2, 3], 1);

        $this->listener->onCustomerPriceListUpdate($event);
    }

    public function testOnCustomerGroupPriceListUpdate()
    {
        $event = new CustomerGroupRelationUpdateEvent([['websiteId' => 1, 'customerGroups' => [2, 3]]]);
        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('invalidateByCustomerGroupsForFlatPricing')
            ->with([2, 3], 1);

        $this->listener->onCustomerGroupPriceListUpdate($event);
    }

    public function testOnWebsitePriceListUpdate()
    {
        $event = new WebsiteRelationUpdateEvent([1]);
        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('invalidateByWebsitesForFlatPricing')
            ->with([1]);

        $this->listener->onWebsitePriceListUpdate($event);
    }

    private function assertRepositoryCall()
    {
        $repo = $this->createMock(ShoppingListTotalRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(ShoppingListTotal::class)
            ->willReturn($repo);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        return $repo;
    }
}
