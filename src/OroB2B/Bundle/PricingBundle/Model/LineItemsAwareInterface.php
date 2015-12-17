<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

interface LineItemsAwareInterface
{
    /**
     * @return ArrayCollection
     */
    public function getLineItems();
}
