<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerGroupRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\MassStorageUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\WebsiteRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Model\FlatPriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FlatPriceListRelationTriggerHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var WebsiteProviderInterface|MockObject
     */
    private $websiteProvider;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $eventDispatcher;

    /**
     * @var ConfigManager|MockObject
     */
    private $configManager;

    /**
     * @var FlatPriceListRelationTriggerHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->handler = new FlatPriceListRelationTriggerHandler(
            $this->websiteProvider,
            $this->eventDispatcher,
            $this->configManager
        );
    }

    public function testHandleConfigChange()
    {
        $websites = [
            $this->getEntity(Website::class, ['id' => 1]),
            $this->getEntity(Website::class, ['id' => 2])
        ];
        $configValues = [
            1 => [
                'scope' => 'app',
                'value' => 1
            ],
            2 => [
                'scope' => 'website',
                'value' => 2
            ],
            3 => [
                'scope' => 'organization',
                'value' => 3
            ],
        ];

        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn($websites);
        $this->configManager->expects($this->once())
            ->method('getValues')
            ->with('oro_pricing.default_price_list', $websites, false, true)
            ->willReturn($configValues);

        $event = new WebsiteRelationUpdateEvent([1, 3]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, WebsiteRelationUpdateEvent::NAME);

        $this->handler->handleConfigChange();
    }

    public function testHandleWebsiteChange()
    {
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $event = new WebsiteRelationUpdateEvent([2]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, WebsiteRelationUpdateEvent::NAME);

        $this->handler->handleWebsiteChange($website);
    }

    public function testHandleWebsiteChangeWithNewWebsite()
    {
        $website = new Website();
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->handler->handleWebsiteChange($website);
    }

    public function testHandleCustomerGroupChange()
    {
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 3]);

        $event = new CustomerGroupRelationUpdateEvent([['websiteId' => 2, 'customerGroups' => [3]]]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, CustomerGroupRelationUpdateEvent::NAME);

        $this->handler->handleCustomerGroupChange($customerGroup, $website);
    }

    public function testHandleCustomerGroupChangeWithNewCustomerGroup()
    {
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $customerGroup = new CustomerGroup();

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->handler->handleCustomerGroupChange($customerGroup, $website);
    }

    public function testHandleCustomerGroupRemove()
    {
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 3]);

        $this->websiteProvider->expects($this->once())
            ->method('getWebsiteIds')
            ->willReturn([1, 2]);

        $event = new CustomerGroupRelationUpdateEvent([
            ['websiteId' => 1, 'customerGroups' => [3]],
            ['websiteId' => 2, 'customerGroups' => [3]]
        ]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, CustomerGroupRelationUpdateEvent::NAME);

        $this->handler->handleCustomerGroupRemove($customerGroup);
    }

    public function testHandleCustomerChange()
    {
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $customer = $this->getEntity(Customer::class, ['id' => 3]);

        $event = new CustomerRelationUpdateEvent([['websiteId' => 2, 'customers' => [3]]]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, CustomerRelationUpdateEvent::NAME);

        $this->handler->handleCustomerChange($customer, $website);
    }

    public function testHandleCustomerChangeWithNewCustomer()
    {
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $customer = new Customer();

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->handler->handleCustomerChange($customer, $website);
    }

    public function testHandlePriceListStatusChange()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $event = new MassStorageUpdateEvent([2]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, MassStorageUpdateEvent::NAME);

        $this->handler->handlePriceListStatusChange($priceList);
    }
}
