<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @var int
     */
    protected $quantity;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var ArrayCollection
     */
    protected $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
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
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return Price
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param Price $price
     * @return $this
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param ArrayCollection $items
     * @return $this
     */
    public function setItems(ArrayCollection $items)
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
            $this->items->add($item);
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
            $this->items->removeElement($item);
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
