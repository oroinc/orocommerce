<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

trait BillingAddressTrait
{
    /**
     * @var OrderAddress
     *
     * @ORM\OneToOne(targetEntity="OroB2B\Bundle\OrderBundle\Entity\OrderAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="billing_address_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $billingAddress;

    /**
     * @var bool
     *
     * @ORM\Column(name="save_billing_address", type="boolean")
     */
    protected $saveBillingAddress = true;

    /**
     * @return OrderAddress
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @param OrderAddress|null $billingAddress
     * @return $this
     */
    public function setBillingAddress(OrderAddress $billingAddress = null)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSaveBillingAddress()
    {
        return $this->saveBillingAddress;
    }

    /**
     * @param boolean $saveBillingAddress
     * @return $this
     */
    public function setSaveBillingAddress($saveBillingAddress)
    {
        $this->saveBillingAddress = $saveBillingAddress;

        return $this;
    }
}
