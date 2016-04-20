<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

trait CheckoutAddressesTrait
{
    use BillingAddressTrait;
    use ShippingAddressTrait;

    /**
     * @var bool
     *
     * @ORM\Column(name="ship_to_billing_address", type="boolean")
     */
    protected $shipToBillingAddress = false;

    /**
     * @return boolean
     */
    public function isShipToBillingAddress()
    {
        return $this->shipToBillingAddress;
    }

    /**
     * @param boolean $shipToBillingAddress
     * @return $this
     */
    public function setShipToBillingAddress($shipToBillingAddress)
    {
        $this->shipToBillingAddress = $shipToBillingAddress;

        return $this;
    }
}
