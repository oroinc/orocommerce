<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CheckoutBundle\Async\Topics;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutSubtotalRepository;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutSubtotalListener;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerGroupCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutSubtotalListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutRepository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var CheckoutSubtotalRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutSubtotalRepository;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $messageProducer;

    /** @var CheckoutSubtotalListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->checkoutSubtotalRepository = $this->createMock(CheckoutSubtotalRepository::class);

        $this->entityManager = $this->createMock(ObjectManager::class);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->listener = new CheckoutSubtotalListener($this->registry, $this->messageProducer);
    }

    public function testOnPriceListUpdate()
    {
        $ids = [1, 3];
        $event = $this->createMock(CombinedPriceListsUpdateEvent::class);

        $event->expects($this->once())
            ->method('getCombinedPriceListIds')
            ->willReturn($ids);

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(CheckoutSubtotal::class)
            ->willReturn($this->checkoutSubtotalRepository);

        $this->checkoutSubtotalRepository
            ->expects($this->once())
            ->method('invalidateByCombinedPriceList')
            ->with($ids);

        $this->assertMessageSent();

        $this->listener->onPriceListUpdate($event);
    }

    public function testOnCustomerPriceListUpdate()
    {
        $customersData = [
            ['customers' => [1, 2], 'websiteId' => 3],
            ['customers' => [4, 5], 'websiteId' => 6],
        ];
        $event = $this->createMock(CustomerCPLUpdateEvent::class);

        $event->expects($this->once())
            ->method('getCustomersData')
            ->willReturn($customersData);

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(CheckoutSubtotal::class)
            ->willReturn($this->checkoutSubtotalRepository);

        $this->checkoutSubtotalRepository
            ->expects($this->exactly(count($customersData)))
            ->method('invalidateByCustomers');

        $this->assertMessageSent();

        $this->listener->onCustomerPriceListUpdate($event);
    }

    public function testOnCustomerGroupPriceListUpdate()
    {
        $customerGroupsData = [
            ['customerGroups' => ['test'], 'websiteId' => 3],
            ['customerGroups' => ['test'], 'websiteId' => 6],
        ];

        $customersData = [
            ['id' => 1],
            ['id' => 2]
        ];

        $event = $this->createMock(CustomerGroupCPLUpdateEvent::class);

        $event->expects($this->once())
            ->method('getCustomerGroupsData')
            ->willReturn($customerGroupsData);

        $fallbackRepository = $this->createMock(PriceListCustomerFallbackRepository::class);

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListCustomerFallback::class, $fallbackRepository],
                [CheckoutSubtotal::class, $this->checkoutSubtotalRepository],
            ]);

        $fallbackRepository->expects($this->exactly(count($customerGroupsData)))
            ->method('getCustomerIdentityByGroup')
            ->willReturn(new \ArrayIterator($customersData));

        $this->checkoutSubtotalRepository
            ->expects($this->exactly(count($customersData)))
            ->method('invalidateByCustomers');

        $this->assertMessageSent();

        $this->listener->onCustomerGroupPriceListUpdate($event);
    }

    public function testOnWebsitePriceListUpdate()
    {
        $websiteIds = [1, 3, 5];

        $customersData = [
            ['id' => 1],
            ['id' => 2]
        ];

        $event = $this->createMock(WebsiteCPLUpdateEvent::class);

        $event->expects($this->once())
            ->method('getWebsiteIds')
            ->willReturn($websiteIds);

        $fallbackRepository = $this->createMock(PriceListCustomerGroupFallbackRepository::class);

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListCustomerGroupFallback::class, $fallbackRepository],
                [CheckoutSubtotal::class, $this->checkoutSubtotalRepository],
            ]);
        $fallbackRepository->expects($this->exactly(count($websiteIds)))
            ->method('getCustomerIdentityByWebsite')
            ->willReturn(new \ArrayIterator($customersData));

        $this->checkoutSubtotalRepository
            ->expects($this->exactly(count($websiteIds)))
            ->method('invalidateByCustomers');

        $this->assertMessageSent();

        $this->listener->onWebsitePriceListUpdate($event);
    }

    public function testOnConfigPriceListUpdate()
    {
        $websitesData = [
            ['id' => 1],
            ['id' => 2]
        ];

        $customersData = [
            ['id' => 1],
            ['id' => 2]
        ];

        $event = $this->createMock(ConfigCPLUpdateEvent::class);

        $fallbackWebsiteRepository = $this->createMock(PriceListWebsiteFallbackRepository::class);
        $fallbackRepository = $this->createMock(PriceListCustomerGroupFallbackRepository::class);

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListWebsiteFallback::class, $fallbackWebsiteRepository],
                [PriceListCustomerGroupFallback::class, $fallbackRepository],
                [CheckoutSubtotal::class, $this->checkoutSubtotalRepository],
            ]);

        $fallbackWebsiteRepository->expects($this->once())
            ->method('getWebsiteIdByDefaultFallback')
            ->willReturn($websitesData);

        $fallbackRepository->expects($this->exactly(count($websitesData)))
            ->method('getCustomerIdentityByWebsite')
            ->willReturn(new \ArrayIterator($customersData));

        $this->checkoutSubtotalRepository
            ->expects($this->exactly(count($websitesData)))
            ->method('invalidateByCustomers');

        $this->assertMessageSent();

        $this->listener->onConfigPriceListUpdate($event);
    }

    protected function assertMessageSent()
    {
        $this->messageProducer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::RECALCULATE_CHECKOUT_SUBTOTALS);
    }
}
