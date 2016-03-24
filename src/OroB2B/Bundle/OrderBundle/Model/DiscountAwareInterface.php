<?php

namespace OroB2B\Bundle\OrderBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\OrderBundle\Entity\OrderDiscount;

interface DiscountAwareInterface
{
    /**
     * @return ArrayCollection|OrderDiscount[]
     */
    public function getDiscounts();
}
