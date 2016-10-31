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

        return $this->paymentMethod;
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

    /**
     * {@inheritdoc}
     */
    public function generateHash()
    {
        $lineItems = array_map(function (ShippingLineItemInterface $item) {
            return $this->lineItemToString($item);
        }, $this->lineItems);

        // if order of line item was changed, hash should not be changed
        usort($lineItems, function ($a, $b) {
            return strcmp(md5($a), md5($b));
        });

        return hash('sha512', implode('', array_merge($lineItems, [
            $this->currency,
            $this->paymentMethod,
            $this->addressToString($this->billingAddress),
            $this->addressToString($this->shippingAddress),
            $this->addressToString($this->shippingOrigin),
            $this->subtotal ? $this->subtotal->getValue() : '',
            $this->subtotal ? $this->subtotal->getCurrency() : '',
        ])));
    }

    /**
     * @param AddressInterface|null $address
     * @return string
     */
    protected function addressToString(AddressInterface $address = null)
    {
        return $address ? implode('', [
            $address->getStreet(),
            $address->getStreet2(),
            $address->getCity(),
            $address->getRegionName(),
            $address->getRegionCode(),
            $address->getPostalCode(),
            $address->getCountryName(),
            $address->getCountryIso2(),
            $address->getCountryIso3(),
            $address->getOrganization(),
        ]) : '';
    }

    /**
     * @param ShippingLineItemInterface $item
     * @return string
     */
    protected function lineItemToString(ShippingLineItemInterface $item)
    {
        $strings = [
            $item->getEntityIdentifier(),
            $item->getQuantity(),
            $item->getProductUnitCode()
        ];

        if ($item->getProduct()) {
            $strings[] = $item->getProduct()->getId();
            $strings[] = $item->getProduct()->getSku();
        }

        if ($item->getPrice()) {
            $strings[] = $item->getPrice()->getValue();
            $strings[] = $item->getPrice()->getCurrency();
        }

        if ($item->getWeight()) {
            $strings[] = $item->getWeight()->getValue();
            if ($item->getWeight()->getUnit()) {
                $strings[] = $item->getWeight()->getUnit()->getCode();
            }
        }

        if ($item->getDimensions()) {
            if ($item->getDimensions()->getValue()) {
                $strings[] = $item->getDimensions()->getValue()->getHeight();
                $strings[] = $item->getDimensions()->getValue()->getLength();
                $strings[] = $item->getDimensions()->getValue()->getWidth();
            }
            if ($item->getDimensions()->getUnit()) {
                $strings[] = $item->getDimensions()->getUnit()->getCode();
            }
        }

        return implode('', $strings);
    }
}
