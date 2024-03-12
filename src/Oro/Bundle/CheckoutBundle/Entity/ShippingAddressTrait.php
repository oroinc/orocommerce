<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * Trait for ORM entities holding Order shipping address.
 */
trait ShippingAddressTrait
{
    #[ORM\OneToOne(targetEntity: OrderAddress::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'shipping_address_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrderAddress $shippingAddress = null;

    #[ORM\Column(name: 'save_shipping_address', type: Types::BOOLEAN, options: ['default' => true])]
    protected ?bool $saveShippingAddress = true;

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
        $this->saveShippingAddress = (bool)$saveShippingAddress;

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
