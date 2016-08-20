<?php

namespace Oro\Bundle\TaxBundle\Model;

final class Result extends AbstractResult
{
    const TOTAL = 'total';
    const SHIPPING = 'shipping';

    const UNIT = 'unit';
    const ROW = 'row';

    const TAXES = 'taxes';
    const ITEMS = 'items';

    /**
     * @var bool
     */
    protected $resultLocked = false;

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

    /**
     * @return Result[]
     */
    public function getItems()
    {
        return $this->getOffset(self::ITEMS, []);
    }

    /** {@inheritdoc} */
    public function serialize()
    {
        if ($this->offsetExists(self::ITEMS)) {
            $this->unsetOffset(self::ITEMS);
        }

        return parent::serialize();
    }

    public function lockResult()
    {
        $this->resultLocked = true;
    }

    public function unlockResult()
    {
        $this->resultLocked = false;
    }

    /**
     * @return bool
     */
    public function isResultLocked()
    {
        return $this->resultLocked;
    }
}
