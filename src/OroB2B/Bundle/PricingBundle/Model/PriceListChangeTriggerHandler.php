<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\DTO\PriceListChangeTrigger;
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
     * @param ManagerRegistry $registry
     * @param PriceListChangeTriggerFactory $triggerFactory
     * @param MessageProducerInterface $producer
     */
    public function __construct(
        ManagerRegistry $registry,
        PriceListChangeTriggerFactory $triggerFactory,
        MessageProducerInterface $producer
    ) {
        $this->registry = $registry;
        $this->triggerFactory = $triggerFactory;
        $this->producer = $producer;
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
        $this->triggersFiller->fillTriggersByPriceList($priceList);
    }

    /**
     * @param AccountGroup $accountGroup
     */
    public function handleAccountGroupRemove(AccountGroup $accountGroup)
    {
//
//        $websiteIds = $this->registry
//            ->getManagerForClass('OroB2BPricingBundle:PriceListToAccountGroup')
//            ->getRepository('OroB2BPricingBundle:PriceListToAccountGroup')
//            ->getWebsiteIdsByAccountGroup($accountGroup);
//
//        if ($websiteIds) {
//            $this->getManager()
//                ->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')
//                ->insertAccountWebsitePairsByAccountGroup(
//                    $accountGroup,
//                    $websiteIds,
//                    $this->insertFromSelectQueryExecutor
//                );
//        }
    }

    /**
     * @param bool|true $andFlush
     */
    public function handleFullRebuild($andFlush = true)
    {
        $trigger = $this->triggerFactory->create();
        $trigger->setForce(true);
        $this->producer->send(self::TOPIC, $trigger->toArray());
    }
}
