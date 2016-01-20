<?php

namespace OroB2B\Bundle\TaxBundle\Model;

final class Result extends AbstractResult
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
        return $this->getOffset(self::TOTAL, new ResultElement());
    }

    /**
     * @return ResultElement
     */
    public function getShipping()
    {
        return $this->getOffset(self::SHIPPING, new ResultElement());
    }

    /**
     * @return ResultElement
     */
    public function getUnit()
    {
        return $this->getOffset(self::UNIT, new ResultElement());
    }

    /**
     * @return ResultElement
     */
    public function getRow()
    {
        return $this->getOffset(self::ROW, new ResultElement());
    }

    /**
     * @return TaxResultElement[]
     */
    public function getTaxes()
    {
        return $this->getOffset(self::TAXES, []);
    }

    /** {@inheritdoc} */
    public function serialize()
    {
        if (!empty($this->getTaxes())) {
            $this->offsetUnset(self::TAXES);
        }

        return parent::serialize();
    }
}
