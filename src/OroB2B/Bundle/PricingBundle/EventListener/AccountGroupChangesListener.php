<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\Accountbundle\Event\AccountEvent;
use OroB2B\Bundle\Accountbundle\Event\AccountGroupEvent;
use OroB2B\Bundle\PricingBundle\Model\DTO\AccountWebsiteDTO;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;

class AccountGroupChangesListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var   PriceListToAccountRepository */
    protected $priceListToAccountRepository;

    /** @var  InsertFromSelectQueryExecutor $executor */
    protected $insertFromSelectQueryExecutor;

    /**
     * @param ManagerRegistry $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
    ) {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
    }

    /**
     * @param AccountEvent $event
     */
    public function onChangeGroupInAccount(AccountEvent $event)
    {
        $accountWebsitePairsByUpdateGroupInAccount = $this->getPriceListToAccountRepository()
            ->getAccountWebsitePairsByAccount($event->getAccount());
        if ($accountWebsitePairsByUpdateGroupInAccount->count() > 0) {
            $this->dispatchAccountWebsitePairs($accountWebsitePairsByUpdateGroupInAccount);
        }
    }

    /**
     * @param AccountGroupEvent $event
     */
    public function onGroupRemove(AccountGroupEvent $event)
    {
        $accountGroup = $event->getAccountGroup();
        /** @var integer[] $websiteIds */
        $websiteIds = $this->registry
            ->getManagerForClass('OroB2BPricingBundle:PriceListToAccountGroup')
            ->getRepository('OroB2BPricingBundle:PriceListToAccountGroup')
            ->getWebsiteIdsByAccountGroup($accountGroup);

        if ($websiteIds) {
            $this->registry->getManagerForClass('OroB2BPricingBundle:ChangedPriceListCollection')
                ->getRepository('OroB2BPricingBundle:ChangedPriceListCollection')
                ->insertAccountWebsitePairsByAccountGroup(
                    $accountGroup,
                    $websiteIds,
                    $this->insertFromSelectQueryExecutor
                );
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
