<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Shipping Methods Configs Rule Destination Postal Code
*
*/
#[ORM\Entity]
#[ORM\Table('oro_ship_method_postal_code')]
class ShippingMethodsConfigsRuleDestinationPostalCode
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: ShippingMethodsConfigsRuleDestination::class, inversedBy: 'postalCodes')]
    #[ORM\JoinColumn(name: 'destination_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ShippingMethodsConfigsRuleDestination $destination = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ShippingMethodsConfigsRuleDestinationPostalCode
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return ShippingMethodsConfigsRuleDestination
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @param ShippingMethodsConfigsRuleDestination $destination
     * @return ShippingMethodsConfigsRuleDestinationPostalCode
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getName();
    }
}
