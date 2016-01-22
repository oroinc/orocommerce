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
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupChangesListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var  integer[] */
    protected $accountGroupIds;

    /** @var  integer[] */
    protected $accountIds;

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
        if ($this->accountGroupIds) {
            if ($this->recalculateByGroupIds($this->accountGroupIds)) {
                $this->accountGroupIds = null;
                $args->getEntityManager()->flush();
            };
        }
        if ($this->accountIds) {
            if ($this->recalculateByAccount($this->accountIds)) {
                $this->accountIds = null;
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
                $changeSet = $unitOfWork->getEntityChangeSet($entity);
                if (isset($changeSet['group']) && !in_array($entity->getId(), $this->accountIds)) {
                    $this->accountIds[] = $entity->getId();
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
            if ($entity instanceof AccountGroup && !in_array($entity->getId(), $this->accountGroupIds)) {
                $this->accountGroupIds[] = $entity->getId();
            }
        }
    }

    /**
     * @param integer[] $accountGroupIds
     * @return bool
     */
    protected function recalculateByGroupIds($accountGroupIds)
    {
        /** @var integer[] $websiteIds */
        $websiteIds = $this->registry
            ->getManagerForClass('OroB2BPricingBundle:PriceListToAccountGroup')
            ->getRepository('OroB2BPricingBundle:PriceListToAccountGroup')
            ->getWebsiteIdsByAccountGroups($accountGroupIds);

        if ($websiteIds) {
            $accountWebsitePairs = $this->getPriceListToAccountRepository()
                ->getAccountWebsitePairsByAccountGroupIds($accountGroupIds, $websiteIds);

            return $this->dispatchAccountWebsitePairs($accountWebsitePairs);
        }

        return false;
    }

    /**
     * @param integer[] $accountIds
     * @return bool
     */
    protected function recalculateByAccount($accountIds)
    {
        $accountWebsitePairs = $this->getPriceListToAccountRepository()
            ->getAccountWebsitePairsByAccountIds($accountIds);

        return $this->dispatchAccountWebsitePairs($accountWebsitePairs);
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

    /**
     * @param $accountWebsitePairs
     * @return bool
     */
    protected function dispatchAccountWebsitePairs($accountWebsitePairs)
    {
        $dispatched = false;
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

        return $dispatched;
    }
}
