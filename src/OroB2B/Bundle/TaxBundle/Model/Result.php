<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

final class Result
{
    /**
     * @var ResultElement
     */
    private $total;

    /**
     * @var ResultElement
     */
    private $shipping;

    /**
     * @var ArrayCollection
     */
    private $taxes;

    /**
     * @param ResultElement   $total
     * @param ResultElement   $shipping
     * @param ArrayCollection $taxes
     */
    public function __construct(ResultElement $total, ResultElement $shipping, ArrayCollection $taxes)
    {
        $this->total = $total;
        $this->shipping = $shipping;
        $this->taxes = $taxes;
    }

    /**
     * @return ResultElement
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @return ResultElement
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * @return ArrayCollection
     */
    public function getTaxes()
    {
        return $this->taxes;
    }
}
