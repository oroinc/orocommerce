<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListChain;
use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListCollectionListener
{
    /** @var  ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param PriceListCollectionChange $event
     */
    public function onChangeCollectionBefore(PriceListCollectionChange $event)
    {
        $changedPriceListCollection = new ChangedPriceListChain();
        $targetEntity = $event->getTargetEntity();
        if ($targetEntity instanceof Website) {
            $changedPriceListCollection->setWebsite($targetEntity);
        } elseif ($targetEntity instanceof Account) {
            $changedPriceListCollection->setAccount($targetEntity);
            $changedPriceListCollection->setWebsite($event->getWebsite());
        } elseif ($targetEntity instanceof AccountGroup) {
            $changedPriceListCollection->setAccountGroup($targetEntity);
            $changedPriceListCollection->setWebsite($event->getWebsite());
        }
        $this->registry
            ->getManagerForClass('OroB2BPricingBundle:ChangedPriceListChain')
            ->persist($changedPriceListCollection);
    }
}
