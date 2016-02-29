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
     * @param ManagerRegistry $registry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ManagerRegistry $registry, EventDispatcherInterface $eventDispatcher)
    {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Website $website
     */
    public function handleWebsiteChange(Website $website)
    {
        $trigger = $this->createTrigger();
        $trigger->setWebsite($website);
        $this->getManager()->persist($trigger);
    }

    /**
     * @param Account $account
     * @param Website $website
     */
    public function handleAccountChange(Account $account, Website $website = null)
    {
        $trigger = $this->createTrigger();
        $trigger->setAccount($account)
            ->setWebsite($website);
        $this->getManager()->persist($trigger);
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
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     */
    public function handleAccountGroupChange(AccountGroup $accountGroup, Website $website = null)
    {
        $trigger = $this->createTrigger();
        $trigger->setAccountGroup($accountGroup)
            ->setWebsite($website);
        $this->getManager()->persist($trigger);
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
        }
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
