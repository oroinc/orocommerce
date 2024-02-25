<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroOrderBundle_Entity_OrderAddress;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Entity\AddressPhoneAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Represents billing and shipping address for an order.
 * @mixin OroOrderBundle_Entity_OrderAddress
 */
#[ORM\Entity]
#[ORM\Table('oro_order_address')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-map-marker'],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'orders']
    ]
)]
class OrderAddress extends AbstractAddress implements
    AddressPhoneAwareInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\ManyToOne(targetEntity: CustomerAddress::class)]
    #[ORM\JoinColumn(name: 'customer_address_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?CustomerAddress $customerAddress = null;

    #[ORM\ManyToOne(targetEntity: CustomerUserAddress::class)]
    #[ORM\JoinColumn(name: 'customer_user_address_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?CustomerUserAddress $customerUserAddress = null;

    #[ORM\Column(name: 'from_external_source', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $fromExternalSource = false;

    #[ORM\Column(name: 'phone', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['contact_information' => 'phone']])]
    protected ?string $phone = null;

    /**
     * Set customerAddress
     *
     * @param CustomerAddress|null $customerAddress
     *
     * @return OrderAddress
     */
    public function setCustomerAddress(CustomerAddress $customerAddress = null)
    {
        $this->customerAddress = $customerAddress;

        return $this;
    }

    /**
     * Get customerUserAddress
     *
     * @return CustomerAddress|null
     */
    public function getCustomerAddress()
    {
        return $this->customerAddress;
    }

    /**
     * Set customerUserAddress
     *
     * @param CustomerUserAddress|null $customerUserAddress
     *
     * @return OrderAddress
     */
    public function setCustomerUserAddress(CustomerUserAddress $customerUserAddress = null)
    {
        $this->customerUserAddress = $customerUserAddress;

        return $this;
    }

    /**
     * Get customerUserAddress
     *
     * @return CustomerUserAddress|null
     */
    public function getCustomerUserAddress()
    {
        return $this->customerUserAddress;
    }

    /**
     * @return boolean
     */
    public function isFromExternalSource()
    {
        return $this->fromExternalSource;
    }

    /**
     * @param boolean $fromExternalSource
     * @return $this
     */
    public function setFromExternalSource($fromExternalSource)
    {
        $this->fromExternalSource = (bool)$fromExternalSource;

        return $this;
    }

    /**
     * Set phone number
     *
     * @param string $phone
     *
     * @return OrderAddress
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone number
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }
}
