<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;

class HandlePriceListStatusChangeListener
{
    /**
     * @internal
     */
    const FIELD_ACTIVE = 'active';

    /**
     * @var PriceListRelationTriggerHandler
     */
    private $priceListChangesHandler;

    /**
     * @var bool
     */
    private $activeHasChanged = false;

    /**
     * @param PriceListRelationTriggerHandler $priceListChangesHandler
     */
    public function __construct(PriceListRelationTriggerHandler $priceListChangesHandler)
    {
        $this->priceListChangesHandler = $priceListChangesHandler;
    }

    /**
     * @param PriceList          $priceList
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PriceList $priceList, PreUpdateEventArgs $event)
    {
        $this->activeHasChanged = $event->hasChangedField(self::FIELD_ACTIVE);
    }

    /**
     * @param PriceList $priceList
     */
    public function postUpdate(PriceList $priceList)
    {
        if ($this->activeHasChanged) {
            $this->priceListChangesHandler->handlePriceListStatusChange($priceList);
            $this->activeHasChanged = false;
        }
    }
}
