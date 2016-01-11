<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

final class Result extends \ArrayObject
{
    const TOTAL = 'total';
    const SHIPPING = 'shipping';
    const TAXES = 'taxes';

    /**
     * @param ResultElement $total
     * @param ResultElement $shipping
     * @param ArrayCollection $taxes
     *
     * @return Result
     */
    public static function create(ResultElement $total, ResultElement $shipping, ArrayCollection $taxes)
    {
        $result = new Result();

        $result->offsetSet(self::TOTAL, $total);
        $result->offsetSet(self::SHIPPING, $shipping);
        $result->offsetSet(self::TAXES, $taxes);

        return $result;
    }

    /**
     * @return ResultElement
     */
    public function getTotal()
    {
        return $this->offsetGet(self::TOTAL);
    }

    /**
     * @return ResultElement
     */
    public function getShipping()
    {
        return $this->offsetGet(self::SHIPPING);
    }

    /**
     * @return ArrayCollection
     */
    public function getTaxes()
    {
        return $this->offsetGet(self::TAXES);
    }
}
