<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Event\PriceListQueueChangeEvent;
use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
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
     * @param PriceListQueueChangeEvent $event
     */
    public function onChangeCollectionBefore(PriceListQueueChangeEvent $event)
    {
        $trigger = new PriceListChangeTrigger();
        $targetEntity = $event->getTargetEntity();
        if ($targetEntity instanceof Website) {
            $trigger->setWebsite($targetEntity);
        } elseif ($targetEntity instanceof Account) {
            $trigger->setAccount($targetEntity);
            $trigger->setWebsite($event->getWebsite());
        } elseif ($targetEntity instanceof AccountGroup) {
            $trigger->setAccountGroup($targetEntity);
            $trigger->setWebsite($event->getWebsite());
        }
        $this->registry
            ->getManagerForClass('OroB2BPricingBundle:PriceListChangeTrigger')
            ->persist($trigger);
    }
}
