<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\TriggersFiller\ScopeRecalculateTriggersFiller;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListChangeTriggerHandler
{
    const TOPIC = 'test';
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ScopeRecalculateTriggersFiller
     */
    protected $triggersFiller;

    /**
     * @var PriceListChangeTriggerFactory
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
     * @param ManagerRegistry $registry
     * @param PriceListChangeTriggerFactory $triggerFactory
     * @param MessageProducerInterface $producer
     * @param ConfigManager $configManager
     */
    public function __construct(
        ManagerRegistry $registry,
        PriceListChangeTriggerFactory $triggerFactory,
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
        $this->producer->send(self::TOPIC, $trigger->toArray());
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
        $this->producer->send(self::TOPIC, $trigger->toArray());
    }

    public function handleConfigChange()
    {
        $trigger = $this->triggerFactory->create();
        $this->producer->send(self::TOPIC, $trigger->toArray());
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
        $this->producer->send(self::TOPIC, $trigger->toArray());
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
            $this->configManager->get('oro_b2b_pricing.default_price_lists')
        );

        if (in_array($priceList->getId(), $configPriceListIds)) {
            $this->handleFullRebuild();
        }

        $priceListToAccountRepository = $this->registry->getRepository(PriceListToAccount::class);
        foreach ($priceListToAccountRepository->getIteratorByPriceList($priceList) as $item) {
            $this->producer->send(self::TOPIC, $item);
        }

        $priceListToAccountGroupRepository = $this->registry->getRepository(PriceListToAccountGroup::class);
        foreach ($priceListToAccountGroupRepository->getIteratorByPriceList($priceList) as $item) {
            $this->producer->send(self::TOPIC, $item);
        }

        $priceListToWebsiteRepository = $this->registry->getRepository(PriceListToWebsite::class);
        foreach ($priceListToWebsiteRepository->getIteratorByPriceList($priceList) as $item) {
            $this->producer->send(self::TOPIC, $item);
        }
    }

    /**
     * @param AccountGroup $accountGroup
     */
    public function handleAccountGroupRemove(AccountGroup $accountGroup)
    {
        $iterator = $this->registry->getRepository(PriceListToAccount::class)
            ->getAccountWebsitePairsByAccountGroupIterator($accountGroup);
        foreach ($iterator as $item) {
            $this->producer->send(self::TOPIC, $item);
        }
    }

    public function handleFullRebuild()
    {
        $trigger = $this->triggerFactory->create();
        $trigger->setForce(true);
        $this->producer->send(self::TOPIC, $trigger->toArray());
    }
}
