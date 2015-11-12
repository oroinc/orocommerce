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
        return implode(PHP_EOL, $this->filter(function (QuickAddRow $row) {
            return sprintf('%s, %s', $row->getSku(), $row->getQuantity());
        })->toArray());
    }

    /**
     * @return QuickAddRow[]
     */
    public function hasCompleteRows()
    {
        return $this->filter(function (QuickAddRow $row) {
            return $row->isComplete();
        })->count() > 0;
    }
}
