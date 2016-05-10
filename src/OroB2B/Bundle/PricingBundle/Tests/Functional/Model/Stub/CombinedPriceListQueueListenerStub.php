<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Model\Stub;

use OroB2B\Bundle\PricingBundle\EventListener\CombinedPriceListQueueListener;

class CombinedPriceListQueueListenerStub extends CombinedPriceListQueueListener
{
    /**
     * Don't need dependencies for stub
     */
    public function __construct()
    {
    }

    /**
     * @return bool
     */
    public function hasCollectionChanges()
    {
        return $this->hasCollectionChanges;
    }
}
