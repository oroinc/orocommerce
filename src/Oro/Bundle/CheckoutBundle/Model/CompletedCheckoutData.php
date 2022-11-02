<?php

namespace Oro\Bundle\CheckoutBundle\Model;

/**
 * Holds completed checkout data
 */
final class CompletedCheckoutData extends \ArrayObject implements \JsonSerializable
{
    const CURRENCY = 'currency';
    const ITEMS_COUNT = 'itemsCount';
    const ORDERS = 'orders';
    const STARTED_FROM = 'startedFrom';
    const SUBTOTAL = 'subtotal';
    const TOTAL = 'total';

    /**
     * Creates new CompletedCheckoutData object from serialized data
     *
     * @param array|null $serialized
     * @return CompletedCheckoutData
     * @throws \InvalidArgumentException
     */
    public static function jsonDeserialize($serialized)
    {
        if ($serialized === null) {
            return new self();
        } elseif (!is_array($serialized)) {
            throw new \InvalidArgumentException(
                'You cannot deserialize CompletedCheckoutData from anything, except array or null'
            );
        }

        return new self($serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * @return int
     */
    public function getItemsCount()
    {
        return $this->getOffset(self::ITEMS_COUNT, 0);
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->getOffset(self::TOTAL, 0);
    }

    /**
     * @return float
     */
    public function getSubtotal()
    {
        return $this->getOffset(self::SUBTOTAL, 0);
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->getOffset(self::CURRENCY, 0);
    }

    /**
     * @return string
     */
    public function getStartedFrom()
    {
        return $this->getOffset(self::STARTED_FROM);
    }

    /**
     * @return array
     */
    public function getOrderData()
    {
        $docs = $this->getOffset(self::ORDERS, []);

        return array_shift($docs);
    }

    /**
     * @param string $offset
     * @param mixed $default
     * @return mixed
     */
    public function getOffset($offset, $default = null)
    {
        if ($this->offsetExists((string)$offset)) {
            return $this->offsetGet((string)$offset);
        }

        return $default;
    }
}
