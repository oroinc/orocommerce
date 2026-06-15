<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * Trait for ORM entities holding Order billing address.
 */
trait BillingAddressTrait
{
    #[ORM\OneToOne(targetEntity: OrderAddress::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'billing_address_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?OrderAddress $billingAddress = null;

    #[ORM\Column(name: 'save_billing_address', type: Types::BOOLEAN, options: ['default' => true])]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?bool $saveBillingAddress = true;

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
