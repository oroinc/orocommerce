<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("oro_ship_method_postal_code")
 */
class ShippingMethodConfigsRuleDestinationPostalCode
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var ShippingMethodConfigsRuleDestination
     *
     * @ORM\ManyToOne(targetEntity="ShippingMethodConfigsRuleDestination")
     * @ORM\JoinColumn(name="destination_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $destination;

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
     * @return ShippingMethodConfigsRuleDestinationPostalCode
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return ShippingMethodConfigsRuleDestination
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @param ShippingMethodConfigsRuleDestination $destination
     * @return ShippingMethodConfigsRuleDestinationPostalCode
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
        return $this->getName();
    }
}
