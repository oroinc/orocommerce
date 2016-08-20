<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Model;

use Doctrine\Common\Collections\ArrayCollection;

interface LineItemsNotPricedAwareInterface
{
    /**
     * @return ArrayCollection
     */
    public function getLineItems();
}
