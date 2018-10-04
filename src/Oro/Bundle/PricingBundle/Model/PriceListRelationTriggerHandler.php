<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Handle price list collection changes.
 */
class PriceListRelationTriggerHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PriceListRelationTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var array|PriceListRelationTrigger[]
     */
    protected $scheduledTriggers = [];

    /**
     * @var bool
     */
    protected $fullRebuildRequested = false;

    /**
     * @var array
     */
    protected $changedWebsites = [];

    /**
     * @var array
     */
    protected $checkGroupFallback = [
        'website' => [],
        'customerGroup' => []
    ];

    /**
     * @var array
     */
    protected $checkCustomerFallback = [
        'website' => [],
        'customer' => []
    ];

    /**
     * @param ManagerRegistry $registry
     * @param PriceListRelationTriggerFactory $triggerFactory
     * @param MessageProducerInterface $producer
     * @param ConfigManager $configManager
     */
    public function __construct(
        ManagerRegistry $registry,
        PriceListRelationTriggerFactory $triggerFactory,
        MessageProducerInterface $producer,
        ConfigManager $configManager
    ) {
        $this->registry = $registry;
        $this->triggerFactory = $triggerFactory;
        $this->producer = $producer;
        $this->configManager = $configManager;

        $this->setDefaultStates();
    }

    public function handleConfigChange()
    {
        if ($this->fullRebuildRequested) {
            return;
        }

        $trigger = $this->triggerFactory->create();
        $this->scheduleTrigger($trigger->toArray());
    }

    /**
     * @param Website $website
     */
    public function handleWebsiteChange(Website $website)
    {
        if ($this->fullRebuildRequested) {
            return;
        }

        $trigger = $this->triggerFactory->create();
        $trigger->setWebsite($website);

        $this->scheduleTrigger($trigger->toArray());

        $this->changedWebsites[$website->getId()] = true;
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     */
    public function handleCustomerGroupChange(CustomerGroup $customerGroup, Website $website)
    {
        if ($this->fullRebuildRequested) {
            return;
        }

        $trigger = $this->triggerFactory->create();
        $trigger->setCustomerGroup($customerGroup)
            ->setWebsite($website);
        $this->scheduleTrigger($trigger->toArray());

        $this->checkGroupFallback['website'][$website->getId()] = true;
        $this->checkGroupFallback['customerGroup'][$customerGroup->getId()] = true;
    }

    /**
     * @param CustomerGroup $customerGroup
     */
    public function handleCustomerGroupRemove(CustomerGroup $customerGroup)
    {
        if ($this->fullRebuildRequested) {
            return;
        }

        $iterator = $this->registry->getRepository(PriceListToCustomer::class)
            ->getCustomerWebsitePairsByCustomerGroupIterator($customerGroup);
        foreach ($iterator as $item) {
            $this->scheduleTrigger($item);

            $this->checkGroupFallback['website'][$item[PriceListRelationTrigger::WEBSITE]] = true;
            $this->checkGroupFallback['customerGroup'][$customerGroup->getId()] = true;
        }
    }

    /**
     * @param Customer $customer
     * @param Website $website
     */
    public function handleCustomerChange(Customer $customer, Website $website)
    {
        if ($this->fullRebuildRequested) {
            return;
        }

        $customerGroup = $customer->getGroup();

        $trigger = $this->triggerFactory->create();
        $trigger->setCustomer($customer)
            ->setCustomerGroup($customerGroup)
            ->setWebsite($website);

        $this->scheduleTrigger($trigger->toArray());

        $this->checkCustomerFallback['website'][$website->getId()] = true;
        $this->checkCustomerFallback['customer'][$customer->getId()] = true;

        if ($customerGroup) {
            $this->checkGroupFallback['website'][$website->getId()] = true;
            $this->checkGroupFallback['customerGroup'][$customerGroup->getId()] = true;
        }
    }

    public function handleFullRebuild()
    {
        $this->fullRebuildRequested = true;

        $trigger = $this->triggerFactory->create();
        $trigger->setForce(true);

        $this->scheduledTriggers = [$trigger->toArray()];
    }

    /**
     * @param PriceList $priceList
     */
    public function handlePriceListStatusChange(PriceList $priceList)
    {
        if ($this->fullRebuildRequested) {
            return;
        }

        $configPriceListIds = array_map(
            function ($priceList) {
                return $priceList['priceList'];
            },
            $this->configManager->get('oro_pricing.default_price_lists')
        );

        if (\in_array($priceList->getId(), $configPriceListIds)) {
            $this->handleFullRebuild();
            return;
        }

        $priceListToCustomerRepository = $this->registry->getRepository(PriceListToCustomer::class);
        foreach ($priceListToCustomerRepository->getIteratorByPriceList($priceList) as $item) {
            $this->scheduleTrigger($item);

            $this->checkCustomerFallback['website'][$item[PriceListRelationTrigger::WEBSITE]] = true;
            $this->checkCustomerFallback['customer'][$item[PriceListRelationTrigger::ACCOUNT]] = true;

            $this->checkGroupFallback['website'][$item[PriceListRelationTrigger::WEBSITE]] = true;
            $this->checkGroupFallback['customerGroup'][$item[PriceListRelationTrigger::ACCOUNT_GROUP]] = true;
        }

        $priceListToCustomerGroupRepository = $this->registry->getRepository(PriceListToCustomerGroup::class);
        foreach ($priceListToCustomerGroupRepository->getIteratorByPriceList($priceList) as $item) {
            $this->scheduleTrigger($item);

            $this->checkGroupFallback['website'][$item[PriceListRelationTrigger::WEBSITE]] = true;
            $this->checkGroupFallback['customerGroup'][$item[PriceListRelationTrigger::ACCOUNT_GROUP]] = true;
        }

        $priceListToWebsiteRepository = $this->registry->getRepository(PriceListToWebsite::class);
        foreach ($priceListToWebsiteRepository->getIteratorByPriceList($priceList) as $item) {
            $this->scheduleTrigger($item);

            $this->changedWebsites[$item[PriceListRelationTrigger::WEBSITE]] = true;
        }
    }

    public function sendScheduledTriggers()
    {
        foreach ($this->getOptimizedScheduledTriggers() as $triggerArray) {
            $this->producer->send(Topics::REBUILD_COMBINED_PRICE_LISTS, $triggerArray);
        }

        $this->setDefaultStates();
    }

    /**
     * Return unique set of triggers that based on fallbacks information and already scheduled triggers.
     *
     * @return \Generator
     */
    protected function getOptimizedScheduledTriggers()
    {
        $preserveGroups = $this->getPreservedGroups();
        $preserveCustomers = $this->getPreservedCustomers();

        foreach ($this->scheduledTriggers as $scheduledTrigger) {
            $website = $scheduledTrigger[PriceListRelationTrigger::WEBSITE] ?? null;
            $group = $scheduledTrigger[PriceListRelationTrigger::ACCOUNT_GROUP] ?? null;
            $customer = $scheduledTrigger[PriceListRelationTrigger::ACCOUNT] ?? null;

            if ($website) {
                if ($customer) {
                    // Update customer only if it will be not updated in scope of group.
                    if (!empty($preserveCustomers[$website . '_' . $customer])
                        || !$this->isReferencedGroupUpdated($scheduledTrigger, $preserveGroups)
                    ) {
                        yield $scheduledTrigger;
                    }

                    continue;
                } elseif ($group) {
                    // Update group only if it will be not updated in scope of website
                    if (empty($this->changedWebsites[$website]) || !empty($preserveGroups[$website . '_' . $group])) {
                        yield $scheduledTrigger;
                    }

                    continue;
                } else {
                    // Send website trigger
                    yield $scheduledTrigger;
                }

                continue;
            }

            yield $scheduledTrigger;
        }
    }

    /**
     * @param array $scheduledTrigger
     * @param array $preserveGroups
     * @return bool
     */
    protected function isReferencedGroupUpdated(array $scheduledTrigger, array $preserveGroups): bool
    {
        $website = $scheduledTrigger[PriceListRelationTrigger::WEBSITE] ?? null;
        $group = $scheduledTrigger[PriceListRelationTrigger::ACCOUNT_GROUP] ?? null;
        $isWebsiteChanged = !empty($this->changedWebsites[$website]);

        $customerGroupTrigger = $scheduledTrigger;
        $customerGroupTrigger[PriceListRelationTrigger::ACCOUNT] = null;

        if (!array_key_exists($this->getTriggerKey($customerGroupTrigger), $this->scheduledTriggers)) {
            return $isWebsiteChanged && empty($preserveGroups[$website . '_' . $group]);
        }

        return true;
    }

    /**
     * @param array $trigger
     */
    protected function scheduleTrigger(array $trigger)
    {
        // Be sure that all required keys present.
        $trigger = array_merge(
            [
                PriceListRelationTrigger::WEBSITE => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::ACCOUNT => null
            ],
            $trigger
        );

        // Add only unique triggers
        $this->scheduledTriggers[$this->getTriggerKey($trigger)] = $trigger;
    }

    protected function setDefaultStates()
    {
        $this->scheduledTriggers = [];
        $this->changedWebsites = [];
        $this->checkCustomerFallback = [
            'website' => [],
            'customer' => []
        ];
        $this->checkGroupFallback = [
            'website' => [],
            'customerGroup' => []
        ];
        $this->fullRebuildRequested = false;
    }

    /**
     * @param array $trigger
     * @return string
     */
    protected function getTriggerKey(array $trigger): string
    {
        $key = sprintf(
            '%d_%d_%d',
            $trigger[PriceListRelationTrigger::WEBSITE],
            $trigger[PriceListRelationTrigger::ACCOUNT_GROUP],
            $trigger[PriceListRelationTrigger::ACCOUNT]
        );

        return $key;
    }

    /**
     * @return array
     */
    protected function getPreservedGroups(): array
    {
        $websites = array_filter(array_keys($this->checkGroupFallback['website']));
        $groups = array_filter(array_keys($this->checkGroupFallback['customerGroup']));

        if ($websites && $groups) {
            $groupFallbackRepository = $this->registry->getRepository(PriceListCustomerGroupFallback::class);
            $groupNonDefaultFallback = $groupFallbackRepository->findBy([
                'website' => $websites,
                'customerGroup' => $groups,
                'fallback' => PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY
            ]);
            $preserveGroups = [];
            foreach ($groupNonDefaultFallback as $fallback) {
                $preserveGroups[$fallback->getWebsite()->getId() . '_' . $fallback->getCustomerGroup()->getId()] = true;
            }

            return $preserveGroups;
        }

        return [];
    }

    /**
     * @return array
     */
    protected function getPreservedCustomers(): array
    {
        $websites = array_filter(array_keys($this->checkCustomerFallback['website']));
        $customers = array_filter(array_keys($this->checkCustomerFallback['customer']));

        if ($websites && $customers) {
            $customerFallbackRepository = $this->registry->getRepository(PriceListCustomerFallback::class);
            $customerNonDefaultFallback = $customerFallbackRepository->findBy([
                'website' => $websites,
                'customer' => $customers,
                'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY
            ]);
            $preserveCustomers = [];
            foreach ($customerNonDefaultFallback as $fallback) {
                $preserveCustomers[$fallback->getWebsite()->getId() . '_' . $fallback->getCustomer()->getId()] = true;
            }

            return $preserveCustomers;
        }

        return [];
    }
}
