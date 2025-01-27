<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroSaleBundle_Entity_QuoteAddress;
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
class QuoteAddress extends AbstractAddress implements
    ExtendEntityInterface,
    AddressPhoneAwareInterface,
    AddressBookAwareInterface,
    AddressValidatedAtAwareInterface
{
    use ExtendEntityTrait;
    use AddressBookAwareTrait;
    use AddressValidatedAtAwareTrait;

    #[ORM\Column(name: 'phone', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['contact_information' => 'phone']])]
    protected ?string $phone = null;

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
