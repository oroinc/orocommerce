<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Doctrine\Common\Collections\Collection;

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
     * @var Collection
     */
    private $taxes;

    /**
     * @param ResultElement   $unit
     * @param ResultElement   $row
     * @param Collection $taxes
     */
    public function __construct(ResultElement $unit, ResultElement $row, Collection $taxes)
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
     * @return Collection
     */
    public function getTaxes()
    {
        return $this->taxes;
    }
}
