<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerGroupRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\MassStorageUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\WebsiteRelationUpdateEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a set of methods to handle price list collection changes.
 */
class FlatPriceListRelationTriggerHandler implements PriceListRelationTriggerHandlerInterface
{
    /**
     * @var WebsiteProviderInterface
     */
    private $websiteProvider;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(
        WebsiteProviderInterface $websiteProvider,
        EventDispatcherInterface $eventDispatcher,
        ConfigManager $configManager
    ) {
        $this->websiteProvider = $websiteProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->configManager = $configManager;
    }

    public function handleFullRebuild(): void
    {
        // No need in full rebuild for flat pricing storage.
    }

    public function handleConfigChange(): void
    {
        $websites = $this->websiteProvider->getWebsites();
        $configValues = $this->configManager->getValues('oro_pricing.default_price_list', $websites, false, true);

        $websiteIds = [];
        foreach ($configValues as $websiteId => $value) {
            if ($value['scope'] !== 'website') {
                $websiteIds[] = $websiteId;
            }
        }

        if ($websiteIds) {
            $event = new WebsiteRelationUpdateEvent($websiteIds);
            $this->eventDispatcher->dispatch($event, WebsiteRelationUpdateEvent::NAME);
        }
    }

    public function handleWebsiteChange(Website $website): void
    {
        if ($website->getId()) {
            $event = new WebsiteRelationUpdateEvent([$website->getId()]);
            $this->eventDispatcher->dispatch($event, WebsiteRelationUpdateEvent::NAME);
        }
    }

    public function handleCustomerGroupChange(CustomerGroup $customerGroup, Website $website): void
    {
        if ($customerGroup->getId()) {
            $event = new CustomerGroupRelationUpdateEvent([
                ['websiteId' => $website->getId(), 'customerGroups' => [$customerGroup->getId()]]
            ]);
            $this->eventDispatcher->dispatch($event, CustomerGroupRelationUpdateEvent::NAME);
        }
    }

    public function handleCustomerGroupRemove(CustomerGroup $customerGroup): void
    {
        $eventData = [];
        foreach ($this->websiteProvider->getWebsiteIds() as $websiteId) {
            $eventData[] = ['websiteId' => $websiteId, 'customerGroups' => [$customerGroup->getId()]];
        }
        $event = new CustomerGroupRelationUpdateEvent($eventData);
        $this->eventDispatcher->dispatch($event, CustomerGroupRelationUpdateEvent::NAME);
    }

    public function handleCustomerChange(Customer $customer, Website $website): void
    {
        if ($customer->getId()) {
            $event = new CustomerRelationUpdateEvent([
                ['websiteId' => $website->getId(), 'customers' => [$customer->getId()]]
            ]);
            $this->eventDispatcher->dispatch($event, CustomerRelationUpdateEvent::NAME);
        }
    }

    public function handlePriceListStatusChange(PriceList $priceList): void
    {
        $event = new MassStorageUpdateEvent([$priceList->getId()]);
        $this->eventDispatcher->dispatch($event, MassStorageUpdateEvent::NAME);
    }
}
