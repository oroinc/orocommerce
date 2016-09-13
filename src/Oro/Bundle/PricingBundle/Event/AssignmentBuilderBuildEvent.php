<?php

namespace Oro\Bundle\PricingBundle\Event;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\EventDispatcher\Event;

class AssignmentBuilderBuildEvent extends Event
{
    const NAME = 'oro_pricing.assignment_rule_builder.build';

    /**
     * @var PriceList
     */
    protected $priceList;

    /**
     * @param PriceList $priceList
     * @return $this
     */
    public function setPriceList(PriceList $priceList)
    {
        $this->priceList = $priceList;
        
        return $this;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }
}
