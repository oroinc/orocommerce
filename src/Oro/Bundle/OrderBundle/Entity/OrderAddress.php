<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroOrderBundle_Entity_OrderAddress;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Model\AddressValidatedAtAwareInterface;
use Oro\Bundle\AddressValidationBundle\Model\AddressValidatedAtAwareTrait;
use Oro\Bundle\CustomerBundle\Entity\AddressBookAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\AddressBookAwareTrait;
use Oro\Bundle\CustomerBundle\Entity\AddressPhoneAwareInterface;
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
    ExtendEntityInterface,
    AddressPhoneAwareInterface,
    AddressBookAwareInterface,
    AddressValidatedAtAwareInterface
{
    use ExtendEntityTrait;
    use AddressBookAwareTrait;
    use AddressValidatedAtAwareTrait;

    #[ORM\Column(name: 'from_external_source', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $fromExternalSource = false;

    #[ORM\Column(name: 'phone', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['contact_information' => 'phone']])]
    protected ?string $phone = null;

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
    #[\Override]
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
    #[\Override]
    public function getPhone()
    {
        return $this->phone;
    }
}
