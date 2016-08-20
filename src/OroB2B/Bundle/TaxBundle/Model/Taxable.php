<?php

namespace Oro\Bundle\TaxBundle\Model;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class Taxable
{
    const DIGITAL_PRODUCT = 'digital_product';
    const PRODUCT_TAX_CODE = 'product_tax_code';
    const ACCOUNT_TAX_CODE = 'account_tax_code';

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
     * @var AbstractAddress
     */
    protected $taxationAddress;

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

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var \ArrayObject
     */
    protected $context;

    public function __construct()
    {
        $this->items = new \SplObjectStorage();
        $this->result = new Result();
        $this->context = new \ArrayObject();
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
     * @return Taxable
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
     * @return Taxable
     */
    public function setOrigin(AbstractAddress $origin = null)
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
     * @return Taxable
     */
    public function setDestination(AbstractAddress $destination = null)
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
     * @return Taxable
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
     * @return Taxable
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
     * @return Taxable
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
     * @return Taxable
     */
    public function setItems(\SplObjectStorage $items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @param Taxable $item
     * @return Taxable
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
     * @return Taxable
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
     * @return Taxable
     */
    public function setResult(Result $result)
    {
        if ($this->result->count() === 0) {
            $this->result = $result;
        }

        return $this;
    }

    /**
     * @param \ArrayObject $arrayObject
     * @return Taxable
     */
    public function setContext(\ArrayObject $arrayObject)
    {
        $this->context = $arrayObject;

        return $this;
    }

    /**
     * @param string $keyName
     * @param mixed  $value
     * @return Taxable
     */
    public function addContext($keyName, $value)
    {
        $this->context->offsetSet($keyName, $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return Taxable
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return \ArrayObject
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string $keyName
     * @return mixed
     */
    public function getContextValue($keyName)
    {
        if ($this->context->offsetExists($keyName)) {
            return $this->context->offsetGet($keyName);
        }

        return null;
    }

    /**
     * @return AbstractAddress
     */
    public function getTaxationAddress()
    {
        return $this->taxationAddress;
    }

    /**
     * @param AbstractAddress $taxationAddress
     * @return Taxable
     */
    public function setTaxationAddress(AbstractAddress $taxationAddress = null)
    {
        $this->taxationAddress = $taxationAddress;

        return $this;
    }

    /**
     * @return Taxable
     */
    public function makeDestinationAddressTaxable()
    {
        $this->taxationAddress = $this->destination;

        return $this;
    }

    /**
     * @return Taxable
     */
    public function makeOriginAddressTaxable()
    {
        $this->taxationAddress = $this->origin;

        return $this;
    }
}
