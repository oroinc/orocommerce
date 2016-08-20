<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Model\Stub;

use Oro\Bundle\PricingBundle\EventListener\CombinedPriceListQueueListener;

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
