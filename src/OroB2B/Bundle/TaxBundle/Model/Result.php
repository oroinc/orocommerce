<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class Result extends \ArrayObject
{
    const TOTAL = 'total';
    const SHIPPING = 'shipping';
    const UNIT = 'unit';
    const ROW = 'row';
    const TAXES = 'taxes';

    /**
     * @return ResultElement
     */
    public function getTotal()
    {
        return $this->getOffset(self::TOTAL);
    }

    /**
     * @return ResultElement
     */
    public function getShipping()
    {
        return $this->getOffset(self::SHIPPING);
    }

    /**
     * @return ResultElement
     */
    public function getUnit()
    {
        return $this->getOffset(self::UNIT);
    }

    /**
     * @return ResultElement
     */
    public function getRow()
    {
        return $this->getOffset(self::ROW);
    }

    /**
     * @return Collection
     */
    public function getTaxes()
    {
        return $this->getOffset(self::TAXES, new ArrayCollection());
    }

    /**
     * @param string $offset
     * @param null $default
     * @return mixed
     */
    protected function getOffset($offset, $default = null)
    {
        if ($this->offsetExists((string)$offset)) {
            return $this->offsetGet((string)$offset);
        }

        return $default;
    }
}
