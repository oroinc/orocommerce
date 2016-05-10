<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\PricingBundle\Event\PriceListQueueChangeEvent;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\RecalculateTriggersFiller\ScopeRecalculateTriggersFiller;

class PriceListChangeTriggerHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var ScopeRecalculateTriggersFiller
     */
    protected $triggersFiller;

    /**
     * @param ManagerRegistry $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param InsertFromSelectQueryExecutor $insertFromSelectExecutor
     * @param ScopeRecalculateTriggersFiller $triggersFiller
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        InsertFromSelectQueryExecutor $insertFromSelectExecutor,
        ScopeRecalculateTriggersFiller $triggersFiller
    ) {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->insertFromSelectQueryExecutor = $insertFromSelectExecutor;
        $this->triggersFiller = $triggersFiller;
    }

    /**
     * @param Website $website
     */
    public function handleWebsiteChange(Website $website)
    {
        $trigger = $this->createTrigger();
        $trigger->setWebsite($website);
        $this->getManager()->persist($trigger);
        $this->dispatchQueueChange();
    }

    /**
     * @param Account $account
     * @param Website $website
     */
    public function handleAccountChange(Account $account, Website $website)
    {
        $trigger = $this->createTrigger();
        $trigger->setAccount($account)
            ->setAccountGroup($account->getGroup())
            ->setWebsite($website);
        $this->getManager()->persist($trigger);
        $this->dispatchQueueChange();
    }

    /**
     * @param bool|true $andFlush
     */
    public function handleConfigChange($andFlush = true)
    {
        $trigger = $this->createTrigger();
        $this->getManager()->persist($trigger);
        if ($andFlush) {
            $this->getManager()->flush();
        }
        $this->dispatchQueueChange();
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     */
    public function handleAccountGroupChange(AccountGroup $accountGroup, Website $website)
    {
        $trigger = $this->createTrigger();
        $trigger->setAccountGroup($accountGroup)
            ->setWebsite($website);
        $this->getManager()->persist($trigger);
        $this->dispatchQueueChange();
    }

    /**
     * @param PriceList $priceList
     */
    public function handlePriceListStatusChange(PriceList $priceList)
    {
        $this->triggersFiller->fillTriggersByPriceList($priceList);
        $this->dispatchQueueChange();
    }

    /**
     * @param AccountGroup $accountGroup
     */
    public function handleAccountGroupRemove(AccountGroup $accountGroup)
    {
        $websiteIds = $this->registry
            ->getManagerForClass('OroB2BPricingBundle:PriceListToAccountGroup')
            ->getRepository('OroB2BPricingBundle:PriceListToAccountGroup')
            ->getWebsiteIdsByAccountGroup($accountGroup);

        if ($websiteIds) {
            $this->getManager()
                ->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')
                ->insertAccountWebsitePairsByAccountGroup(
                    $accountGroup,
                    $websiteIds,
                    $this->insertFromSelectQueryExecutor
                );
            $this->dispatchQueueChange();
        }
    }

    /**
     * @param bool|true $andFlush
     */
    public function handleFullRebuild($andFlush = true)
    {
        $trigger = $this->createTrigger();
        $trigger->setForce(true);
        $this->getManager()->persist($trigger);
        if ($andFlush) {
            $this->getManager()->flush();
        }
        $this->dispatchQueueChange();
    }

    protected function dispatchQueueChange()
    {
        $event = new PriceListQueueChangeEvent();
        $this->eventDispatcher->dispatch(PriceListQueueChangeEvent::BEFORE_CHANGE, $event);
    }

    /**
     * @return PriceListChangeTrigger
     */
    protected function createTrigger()
    {
        return new PriceListChangeTrigger();
    }

    /**
     * @return ObjectManager|null
     */
    protected function getManager()
    {
        return $this->registry
            ->getManagerForClass('OroB2BPricingBundle:PriceListChangeTrigger');
    }
}
