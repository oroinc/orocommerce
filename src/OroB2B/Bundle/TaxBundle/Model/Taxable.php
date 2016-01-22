<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CurrencyBundle\Model\Price;

class Taxable
{
    /**
     * @var int
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var AbstractAddress
     */
    protected $origin;

    /**
     * @var AbstractAddress
     */
    protected $destination;

    /**
     * @var BigInteger
     */
    protected $quantity;

    /**
     * @var BigDecimal
     */
    protected $price;

    /**
     * @var BigDecimal
     */
    protected $amount;

    /**
     * @var \SplObjectStorage
     */
    protected $items;

    public function __construct()
    {
        $this->items = new \SplObjectStorage();
        $this->quantity = BigInteger::one();
    }

    /**
     * @return int
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param int $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return AbstractAddress
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param AbstractAddress $origin
     * @return $this
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return AbstractAddress
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @param AbstractAddress $destination
     * @return $this
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * @return BigInteger
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param BigInteger $quantity
     * @return $this
     */
    public function setQuantity(BigInteger $quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @param mixed $quantity
     * @return $this
     */
    public function setRawQuantity($quantity)
    {
        $this->quantity = BigInteger::of($quantity);

        return $this;
    }

    /**
     * @return BigDecimal
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param BigDecimal $price
     * @return $this
     */
    public function setPrice(BigDecimal $price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @param Price $price
     * @return $this
     */
    public function setRawPrice(Price $price)
    {
        $this->price = BigDecimal::of($price->getValue());

        return $this;
    }

    /**
     * @return BigDecimal
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param BigDecimal $amount
     * @return $this
     */
    public function setAmount(BigDecimal $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @param mixed $amount
     * @return $this
     */
    public function setRawAmount($amount)
    {
        $this->amount = BigDecimal::of($amount);

        return $this;
    }

    /**
     * @return \SplObjectStorage
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param \SplObjectStorage $items
     * @return $this
     */
    public function setItems(\SplObjectStorage $items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @param mixed $item
     * @return $this
     */
    public function addItem($item)
    {
        if (!$this->items->contains($item)) {
            $this->items->attach($item);
        }

        return $this;
    }

    /**
     * @param mixed $item
     * @return $this
     */
    public function removeItem($item)
    {
        if ($this->items->contains($item)) {
            $this->items->detach($item);
        }

        return $this;
    }

    /**
     * @param string $className
     * @return Taxable
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}
