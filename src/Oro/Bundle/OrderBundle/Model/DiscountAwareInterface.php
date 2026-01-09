<?php

namespace Oro\Bundle\OrderBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;

/**
 * Defines the contract for entities that support discount information.
 *
 * Implementations of this interface represent entities that can have discounts applied to them,
 * providing access to the collection of associated discount records.
 */
interface DiscountAwareInterface
{
    /**
     * @return ArrayCollection|OrderDiscount[]
     */
    public function getDiscounts();
}
