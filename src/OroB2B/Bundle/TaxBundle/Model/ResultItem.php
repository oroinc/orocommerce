<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

final class ResultItem
{
    /**
     * @var ResultElement
     */
    private $unit;

    /**
     * @var ResultElement
     */
    private $row;

    /**
     * @var ArrayCollection
     */
    private $taxes;

    /**
     * @param ResultElement   $unit
     * @param ResultElement   $row
     * @param ArrayCollection $taxes
     */
    public function __construct(ResultElement $unit, ResultElement $row, ArrayCollection $taxes)
    {
        $this->unit = $unit;
        $this->row = $row;
        $this->taxes = $taxes;
    }

    /**
     * @return ResultElement
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @return ResultElement
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * @return ArrayCollection
     */
    public function getTaxes()
    {
        return $this->taxes;
    }
}
