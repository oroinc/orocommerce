<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\TaxBundle\DependencyInjection\OroB2BTaxExtension;

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
    protected $quantity = 1;

    /**
     * @var string
     */
    protected $price = 0;

    /**
     * @var string
     */
    protected $amount = 0;

    /**
     * @var \SplObjectStorage|Taxable[]
     */
    protected $items;

    /**
     * @var Result
     */
    protected $result;

    public function __construct()
    {
        $this->items = new \SplObjectStorage();
        $this->result = new Result();
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
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $price
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = (string)$price;

        return $this;
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = (string)$amount;

        return $this;
    }

    /**
     * @return \SplObjectStorage|Taxable[]
     */
    public function getItems()
    {
        $this->items->rewind();

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
     * @param Taxable $item
     * @return $this
     */
    public function addItem(Taxable $item)
    {
        if (!$this->items->contains($item)) {
            $this->items->attach($item);
        }

        return $this;
    }

    /**
     * @param Taxable $item
     * @return $this
     */
    public function removeItem(Taxable $item)
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
        $this->className = (string)$className;

        return $this;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return Result
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param Result $result
     */
    public function setResult(Result $result)
    {
        $this->result = $result;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return spl_object_hash($this);
    }
}
