<?php

namespace Oro\Bundle\ShippingBundle\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;

class ShippingContext implements ShippingContextInterface
{
    /**
     * @var object
     */
    private $sourceEntity;

    /**
     * @var mixed
     */
    private $sourceEntityIdentifier;

    /**
     * @var ShippingLineItemInterface[]
     */
    private $lineItems = [];

    /**
     * @var AddressInterface
     */
    private $billingAddress;

    /**
     * @var AddressInterface
     */
    private $shippingAddress;

    /**
     * @var AddressInterface
     */
    private $shippingOrigin;

    /**
     * @var string
     */
    private $paymentMethod;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var Price
     */
    private $subtotal;

    /**
     * @return object
     */
    public function getSourceEntity()
    {
        return $this->sourceEntity;
    }

    /**
     * @param object $sourceEntity
     * @return $this
     */
    public function setSourceEntity($sourceEntity)
    {
        $this->sourceEntity = $sourceEntity;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSourceEntityIdentifier()
    {
        return $this->sourceEntityIdentifier;
    }

    /**
     * @param mixed $sourceEntityIdentifier
     * @return $this
     */
    public function setSourceEntityIdentifier($sourceEntityIdentifier)
    {
        $this->sourceEntityIdentifier = $sourceEntityIdentifier;
        return $this;
    }

    /**
     * @param array $items
     * @return $this
     */
    public function setLineItems(array $items)
    {
        $this->lineItems = [];

        foreach ($items as $item) {
            $this->lineItems[] = $this->createLineItem($item);
        }

        return $this;
    }

    /**
     * @param mixed $item
     * @return ShippingLineItem
     */
    private function createLineItem($item)
    {
        $shippingLineItem = new ShippingLineItem();

        if ($item instanceof ProductHolderInterface) {
            $shippingLineItem->setProductHolder($item);
            $shippingLineItem->setProduct($item->getProduct());
        }

        if ($item instanceof ProductUnitHolderInterface) {
            $shippingLineItem->setProductUnit($item->getProductUnit());
        }

        if ($item instanceof ProductShippingOptionsInterface) {
            $shippingLineItem->setProduct($item->getProduct());
            $shippingLineItem->setProductUnit($item->getProductUnit());
            $shippingLineItem->setWeight($item->getWeight());
            $shippingLineItem->setDimensions($item->getDimensions());
        }

        if ($item instanceof QuantityAwareInterface) {
            $shippingLineItem->setQuantity($item->getQuantity());
        }

        if ($item instanceof PriceAwareInterface) {
            $shippingLineItem->setPrice($item->getPrice());
        }

        return $shippingLineItem;
    }

    /**
     * @return ShippingLineItemInterface[]
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param AddressInterface $address
     * @return $this
     */
    public function setBillingAddress(AddressInterface $address)
    {
        $this->billingAddress = $address;

        return $this;
    }

    /**
     * @return AddressInterface
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @param AddressInterface $address
     * @return $this
     */
    public function setShippingAddress(AddressInterface $address)
    {
        $this->shippingAddress = $address;

        return $this;
    }

    /**
     * @return AddressInterface
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @param AddressInterface $address
     * @return $this
     */
    public function setShippingOrigin(AddressInterface $address)
    {
        $this->shippingOrigin = $address;

        return $this;
    }

    /**
     * @return AddressInterface
     */
    public function getShippingOrigin()
    {
        return $this->shippingOrigin;
    }

    /**
     * @param string $paymentMethod
     * @return string
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return String|null
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return String
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param Price $subtotal
     * @return $this
     */
    public function setSubtotal(Price $subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * @return Price
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }
}
