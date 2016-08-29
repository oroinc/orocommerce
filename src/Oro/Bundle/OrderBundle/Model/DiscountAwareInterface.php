<?php

namespace Oro\Bundle\OrderBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;

interface DiscountAwareInterface
{
    /**
     * @return ArrayCollection|OrderDiscount[]
     */
    public function getDiscounts();
}
