<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccount;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

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
    }

    /**
     * @param Website $website
     */
    public function handleWebsiteChange(Website $website)
    {
        $trigger = $this->triggerFactory->create();
        $trigger->setWebsite($website);
        $this->scheduledTriggers[] = $trigger->toArray();
    }

    /**
     * @param Account $account
     * @param Website $website
     */
    public function handleAccountChange(Account $account, Website $website)
    {
        $trigger = $this->triggerFactory->create();
        $trigger->setAccount($account)
            ->setAccountGroup($account->getGroup())
            ->setWebsite($website);
        $this->scheduledTriggers[] = $trigger->toArray();
    }

    public function handleConfigChange()
    {
        $trigger = $this->triggerFactory->create();
        $this->scheduledTriggers[] = $trigger->toArray();
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     */
    public function handleAccountGroupChange(AccountGroup $accountGroup, Website $website)
    {
        $trigger = $this->triggerFactory->create();
        $trigger->setAccountGroup($accountGroup)
            ->setWebsite($website);
        $this->scheduledTriggers[] = $trigger->toArray();
    }

    /**
     * @param PriceList $priceList
     */
    public function handlePriceListStatusChange(PriceList $priceList)
    {
        $configPriceListIds = array_map(
            function ($priceList) {
                return $priceList['priceList'];
            },
            $this->configManager->get('oro_pricing.default_price_lists')
        );

        if (in_array($priceList->getId(), $configPriceListIds)) {
            $this->handleFullRebuild();
        }

        $priceListToAccountRepository = $this->registry->getRepository(PriceListToAccount::class);
        foreach ($priceListToAccountRepository->getIteratorByPriceList($priceList) as $item) {
            $this->scheduledTriggers[] = $item;
        }

        $priceListToAccountGroupRepository = $this->registry->getRepository(PriceListToAccountGroup::class);
        foreach ($priceListToAccountGroupRepository->getIteratorByPriceList($priceList) as $item) {
            $this->scheduledTriggers[] = $item;
        }

        $priceListToWebsiteRepository = $this->registry->getRepository(PriceListToWebsite::class);
        foreach ($priceListToWebsiteRepository->getIteratorByPriceList($priceList) as $item) {
            $this->scheduledTriggers[] = $item;
        }
    }

    public function sendScheduledTriggers()
    {
        foreach ($this->scheduledTriggers as $triggerArray) {
            $this->producer->send(Topics::REBUILD_COMBINED_PRICE_LISTS, $triggerArray);
        }
        $this->scheduledTriggers = [];
    }

    /**
     * @param AccountGroup $accountGroup
     */
    public function handleAccountGroupRemove(AccountGroup $accountGroup)
    {
        $iterator = $this->registry->getRepository(PriceListToAccount::class)
            ->getAccountWebsitePairsByAccountGroupIterator($accountGroup);
        foreach ($iterator as $item) {
            $this->scheduledTriggers[] = $item;
        }
    }

    public function handleFullRebuild()
    {
        $trigger = $this->triggerFactory->create();
        $trigger->setForce(true);
        $this->producer->send(Topics::REBUILD_COMBINED_PRICE_LISTS, $trigger->toArray());
    }
}
