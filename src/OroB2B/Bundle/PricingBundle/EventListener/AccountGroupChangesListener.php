<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    /** @var  array */
    protected $accountWebsitePairsByRemoveGroup;

    /** @var  array */
    protected $accountWebsitePairsByUpdateGroupInAccount;

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
        $this->accountWebsitePairsByUpdateGroupInAccount = [];
        $this->accountGroupIds = [];
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->accountWebsitePairsByRemoveGroup) {
            $this->dispatchAccountWebsitePairs($this->accountWebsitePairsByRemoveGroup);
            $this->accountWebsitePairsByRemoveGroup = [];
            $args->getEntityManager()->flush();
        };
        if ($this->accountWebsitePairsByUpdateGroupInAccount) {
            $this->dispatchAccountWebsitePairs($this->accountWebsitePairsByUpdateGroupInAccount);
            $this->accountWebsitePairsByUpdateGroupInAccount = [];
            $args->getEntityManager()->flush();
        };
    }

    /**
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $accountIds = [];
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Account) {
                $changeSet = $unitOfWork->getEntityChangeSet($entity);
                if (isset($changeSet['group']) && !in_array($entity->getId(), $accountIds)) {
                    $accountIds[] = $entity->getId();
                }
            }
        }
        if ($accountIds) {
            $this->accountWebsitePairsByUpdateGroupInAccount = $this->getPriceListToAccountRepository()
                ->getAccountWebsitePairsByAccountIds($accountIds);
        }
    }

    /**
     * @param PreFlushEventArgs $event
     * @return bool
     */
    public function preFlush(PreFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $accountGroupIds = [];
        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof AccountGroup && !in_array($entity->getId(), $accountGroupIds)) {
                $accountGroupIds[] = $entity->getId();
            }
        }
        if ($accountGroupIds) {
            /** @var integer[] $websiteIds */
            $websiteIds = $this->registry
                ->getManagerForClass('OroB2BPricingBundle:PriceListToAccountGroup')
                ->getRepository('OroB2BPricingBundle:PriceListToAccountGroup')
                ->getWebsiteIdsByAccountGroups($accountGroupIds);

            if ($websiteIds) {
                $this->accountWebsitePairsByRemoveGroup = $this->getPriceListToAccountRepository()
                    ->getAccountWebsitePairsByAccountGroupIds($accountGroupIds, $websiteIds);
            }
        }
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
     */
    protected function dispatchAccountWebsitePairs($accountWebsitePairs)
    {
        foreach ($accountWebsitePairs as $accountWebsitePair) {
            /** @var Account $account */
            $account = $accountWebsitePair['account'];
            /** @var Website $website */
            $website = $accountWebsitePair['website'];
            $this->eventDispatcher->dispatch(
                PriceListCollectionChange::BEFORE_CHANGE,
                new PriceListCollectionChange($account, $website)
            );
        }
    }
}
