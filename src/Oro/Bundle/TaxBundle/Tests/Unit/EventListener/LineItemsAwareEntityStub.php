<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

class LineItemsAwareEntityStub implements LineItemsAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return new ArrayCollection([]);
    }
}
