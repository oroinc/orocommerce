<?php

namespace Oro\Bundle\TaxBundle\Model;

final class Result extends AbstractResult implements \JsonSerializable
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
     * Creates new Result object from serialized data
     * @param array|null $serialized
     * @return Result
     * @throws \InvalidArgumentException
     */
    public static function jsonDeserialize($serialized)
    {
        if ($serialized === null) {
            return new self();
        } elseif (!is_array($serialized)) {
            throw new \InvalidArgumentException('You cannot deserialize Result from anything, except array or null');
        }

        $result = new self($serialized);
        $result->deserializeAsResultElement(self::TOTAL, $serialized);
        $result->deserializeAsResultElement(self::SHIPPING, $serialized);
        $result->deserializeAsResultElement(self::UNIT, $serialized);
        $result->deserializeAsResultElement(self::ROW, $serialized);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $this->prepareToSerialization();
        return $this->getArrayCopy();
    }

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
        $this->prepareToSerialization();
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

    /**
     * @param string $key
     * @param array $serialized
     */
    protected function deserializeAsResultElement($key, array $serialized)
    {
        if (!empty($serialized[$key]) && is_array($serialized[$key])) {
            $this->offsetSet($key, new ResultElement($serialized[$key]));
        }
    }

    protected function prepareToSerialization()
    {
        if ($this->offsetExists(self::ITEMS)) {
            $this->unsetOffset(self::ITEMS);
        }
        $this->unlockResult();
    }
}
