<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model;

use Doctrine\Common\Collections\ArrayCollection;

interface LineItemsAwareInterface
{
    /**
     * @return ArrayCollection|array
     */
    public function getLineItems();
}
