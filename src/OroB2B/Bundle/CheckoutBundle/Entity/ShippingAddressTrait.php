<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

trait ShippingAddressTrait
{
    /**
     * @var OrderAddress
     *
     * @ORM\OneToOne(targetEntity="OroB2B\Bundle\OrderBundle\Entity\OrderAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="shipping_address_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $shippingAddress;

    /**
     * @var bool
     *
     * @ORM\Column(name="save_shipping_address", type="boolean")
     */
    protected $saveShippingAddress = true;

    /**
     * @return boolean
     */
    public function isSaveShippingAddress()
    {
        return $this->saveShippingAddress;
    }

    /**
     * @param boolean $saveShippingAddress
     * @return $this
     */
    public function setSaveShippingAddress($saveShippingAddress)
    {
        $this->saveShippingAddress = $saveShippingAddress;

        return $this;
    }

    /**
     * @return OrderAddress
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @param OrderAddress|null $shippingAddress
     * @return $this
     */
    public function setShippingAddress(OrderAddress $shippingAddress = null)
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }
}
