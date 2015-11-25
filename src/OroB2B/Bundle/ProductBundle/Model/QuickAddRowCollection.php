<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

class QuickAddRowCollection extends ArrayCollection
{
    /**
     * @return string
     */
    public function __toString()
    {
        return implode(PHP_EOL, $this->map(function (QuickAddRow $row) {
            return sprintf('%s, %s', $row->getSku(), $row->getQuantity());
        })->toArray());
    }

    /**
     * @return QuickAddRow[]
     */
    public function hasCompleteRows()
    {
        return $this->getCompleteRows()->count() > 0;
    }

    /**
     * @return $this
     */
    public function getCompleteRows()
    {
        return $this->filter(function (QuickAddRow $row) {
            return $row->isComplete();
        });
    }

    /**
     * @return $this
     */
    public function getValidRows()
    {
        return $this->filter(function (QuickAddRow $row) {
            return $row->isValid();
        });
    }

    /**
     * @return $this
     */
    public function getInvalidRows()
    {
        return $this->filter(function (QuickAddRow $row) {
            return !$row->isValid();
        });
    }
}
