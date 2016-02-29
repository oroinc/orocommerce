<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\Accountbundle\Event\AccountEvent;
use OroB2B\Bundle\Accountbundle\Event\AccountGroupEvent;
use OroB2B\Bundle\PricingBundle\Model\DTO\AccountWebsiteDTO;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;

class AccountGroupChangesListener
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var  PriceListChangeTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var   PriceListToAccountRepository
     */
    protected $priceListToAccountRepository;

    /**
     * @param ManagerRegistry $registry
     * @param PriceListChangeTriggerHandler $triggerFactory
     */
    public function __construct(
        ManagerRegistry $registry,
        PriceListChangeTriggerHandler $triggerFactory
    ) {
        $this->registry = $registry;
        $this->triggerHandler = $triggerFactory;
    }

    /**
     * @param AccountEvent $event
     */
    public function onChangeGroupInAccount(AccountEvent $event)
    {
        $accountWebsitePairsByUpdateGroupInAccount = $this->getPriceListToAccountRepository()
            ->getAccountWebsitePairsByAccount($event->getAccount());
        if ($accountWebsitePairsByUpdateGroupInAccount->count() > 0) {
            $this->triggerPriceListChanges($accountWebsitePairsByUpdateGroupInAccount);
        }
    }

    /**
     * @param AccountGroupEvent $event
     */
    public function onGroupRemove(AccountGroupEvent $event)
    {
        $this->triggerHandler->handleAccountGroupRemove($event->getAccountGroup());
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
    protected function triggerPriceListChanges($accountWebsitePairs)
    {
        foreach ($accountWebsitePairs as $accountWebsitePair) {
            $this->triggerHandler
                ->handleAccountChange($accountWebsitePair->getAccount(), $accountWebsitePair->getWebsite());
        }
    }
}
