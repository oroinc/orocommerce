<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use OroB2B\Bundle\PricingBundle\Entity\DTO\AccountWebsiteDTO;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class AccountGroupChangesListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var  AccountWebsiteDTO[]|ArrayCollection */
    protected $accountWebsitePairsByRemoveGroup;

    /** @var  AccountWebsiteDTO[]|ArrayCollection */
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
        $this->accountWebsitePairsByUpdateGroupInAccount = new ArrayCollection();
        $this->accountWebsitePairsByRemoveGroup = new ArrayCollection();
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->accountWebsitePairsByRemoveGroup->count() > 0) {
            $this->dispatchAccountWebsitePairs($this->accountWebsitePairsByRemoveGroup);
            $this->accountWebsitePairsByRemoveGroup = new ArrayCollection();
            $args->getEntityManager()->flush();
        };
        if ($this->accountWebsitePairsByUpdateGroupInAccount->count() > 0) {
            $this->dispatchAccountWebsitePairs($this->accountWebsitePairsByUpdateGroupInAccount);
            $this->accountWebsitePairsByUpdateGroupInAccount = new ArrayCollection();
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
     * @param AccountWebsiteDTO[]|ArrayCollection $accountWebsitePairs
     */
    protected function dispatchAccountWebsitePairs($accountWebsitePairs)
    {
        foreach ($accountWebsitePairs as $accountWebsitePair) {

            $this->eventDispatcher->dispatch(
                PriceListCollectionChange::BEFORE_CHANGE,
                new PriceListCollectionChange($accountWebsitePair->getAccount(), $accountWebsitePair->getWebsite())
            );
        }
    }
}
