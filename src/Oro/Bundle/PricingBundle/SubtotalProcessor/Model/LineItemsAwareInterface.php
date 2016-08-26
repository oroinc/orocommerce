<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Model;

use Doctrine\Common\Collections\ArrayCollection;

interface LineItemsAwareInterface
{
    /**
     * @return ArrayCollection
     */
    public function getLineItems();
}
