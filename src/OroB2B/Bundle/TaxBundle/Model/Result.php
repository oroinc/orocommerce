<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
     * @var Collection
     */
    private $taxes;

    /**
     * @param ResultElement   $total
     * @param ResultElement   $shipping
     * @param Collection $taxes
     */
    public function __construct(ResultElement $total, ResultElement $shipping, Collection $taxes)
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
     * @return Collection
     */
    public function getTaxes()
    {
        return $this->taxes;
    }
}
