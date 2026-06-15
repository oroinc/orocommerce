<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
 * Trait for ORM entities holding Order addresses.
 */
trait CheckoutAddressesTrait
{
    use BillingAddressTrait;
    use ShippingAddressTrait;

    #[ORM\Column(name: 'ship_to_billing_address', type: Types::BOOLEAN, options: ['default' => false])]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?bool $shipToBillingAddress = false;

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
        $this->shipToBillingAddress = (bool)$shipToBillingAddress;

        return $this;
    }
}
