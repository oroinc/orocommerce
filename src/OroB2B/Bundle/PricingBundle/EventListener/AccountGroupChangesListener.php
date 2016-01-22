<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class AccountGroupChangesListener
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var  EventDispatcherInterface
     */
    protected $eventDispatcher;

    /** @var  AccountGroup */
    protected $accountGroup;

    /** @var  Account */
    protected $account;

    /** @var   PriceListToAccountRepository */
    protected $priceListToAccountRepository;

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
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->accountGroup) {
            if ($this->recalculateByGroup($this->accountGroup)) {
                $this->accountGroup = null;
                $args->getEntityManager()->flush();
            };
        }
        if ($this->account) {
            if ($this->recalculateByAccount($this->account)) {
                $this->account = null;
                $args->getEntityManager()->flush();
            }
        }
    }

    /**
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Account) {
                $changeSet = $event->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);
                if (isset($changeSet['group'])) {
                    $this->account = $entity;
                }
            }
        }

    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof AccountGroup) {
                $this->accountGroup = $entity;
            }
        }
    }

    /**
     * @param AccountGroup $accountGroup
     * @return bool
     */
    protected function recalculateByGroup(AccountGroup $accountGroup)
    {
        $dispatched = false;
        /** @var integer[] $websiteIds */
        $websiteIds = $this->registry
            ->getManagerForClass('OroB2BPricingBundle:PriceListToAccountGroup')
            ->getRepository('OroB2BPricingBundle:PriceListToAccountGroup')
            ->getWebsiteIdsByAccountGroup($accountGroup);

        if ($websiteIds) {
            $accountWebsitePairs = $this->getPriceListToAccountRepository()
                ->getAccountWebsitePairsByAccountGroup($accountGroup, $websiteIds);
            foreach ($accountWebsitePairs as $accountWebsitePair) {
                /** @var Account $account */
                $account = $accountWebsitePair['account'];
                /** @var Website $website */
                $website = $accountWebsitePair['website'];
                $this->eventDispatcher->dispatch(
                    PriceListCollectionChange::BEFORE_CHANGE,
                    new PriceListCollectionChange($account, $website)
                );
                $dispatched = true;
            }
        }

        return $dispatched;
    }

    /**
     * @param Account $account
     * @return bool
     */
    protected function recalculateByAccount(Account $account)
    {
        $dispatched = false;

        $websites = $this->getPriceListToAccountRepository()->getWebsitesByAccount($account);
        foreach ($websites as $website) {
            $this->eventDispatcher->dispatch(
                PriceListCollectionChange::BEFORE_CHANGE,
                new PriceListCollectionChange($account, $website)
            );
            $dispatched = true;

        }

        return $dispatched;
    }

    /**
     * @return PriceListToAccountRepository
     */
    protected function getPriceListToAccountRepository()
    {
        if (!$this->priceListToAccountRepository) {
            $this->priceListToAccountRepository = $this->registry
                ->getManagerForClass('OroB2BPricingBundle:PriceListToAccount')
                ->getRepository('OroB2BPricingBundle:PriceListToAccount');
        }

        return $this->priceListToAccountRepository;
    }
}
