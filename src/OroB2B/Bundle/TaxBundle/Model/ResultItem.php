<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Doctrine\Common\Collections\Collection;

final class ResultItem extends \ArrayObject
{
    const UNIT = 'unit';
    const ROW = 'row';
    const TAXES = 'taxes';

    /**
     * @param ResultElement $unit
     * @param ResultElement $row
     * @param Collection $taxes
     *
     * @return ResultItem
     */
    public static function create(ResultElement $unit, ResultElement $row, Collection $taxes)
    {
        $resultItem = new ResultItem();

        $resultItem->offsetSet(self::UNIT, $unit);
        $resultItem->offsetSet(self::ROW, $row);
        $resultItem->offsetSet(self::TAXES, $taxes);

        return $resultItem;
    }

    /**
     * @return ResultElement
     */
    public function getUnit()
    {
        return $this->offsetGet(self::UNIT);
    }

    /**
     * @return ResultElement
     */
    public function getRow()
    {
        return $this->offsetGet(self::ROW);
    }

    /**
     * @return Collection
     */
    public function getTaxes()
    {
        return $this->offsetGet(self::TAXES);
    }
}
