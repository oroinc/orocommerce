<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroSaleBundle_Entity_QuoteAddress;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Quote address entity
 * @mixin OroSaleBundle_Entity_QuoteAddress
 */
#[ORM\Entity]
#[ORM\Table('oro_quote_address')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-map-marker'],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class QuoteAddress extends AbstractAddress implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\ManyToOne(targetEntity: CustomerAddress::class)]
    #[ORM\JoinColumn(name: 'customer_address_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?CustomerAddress $customerAddress = null;

    #[ORM\ManyToOne(targetEntity: CustomerUserAddress::class)]
    #[ORM\JoinColumn(name: 'customer_user_address_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?CustomerUserAddress $customerUserAddress = null;

    #[ORM\Column(name: 'phone', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['contact_information' => 'phone']])]
    protected ?string $phone = null;

    /**
     * Set customerAddress
     *
     * @param CustomerAddress|null $customerAddress
     *
     * @return QuoteAddress
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
     * @return QuoteAddress
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
     * Set phone number
     *
     * @param string $phone
     *
     * @return QuoteAddress
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
