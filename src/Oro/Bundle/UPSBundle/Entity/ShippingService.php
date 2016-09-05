<?php

namespace Oro\Bundle\UPSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AddressBundle\Entity\Country;

/**
 * @ORM\Table(name="oro_ups_shipping_service")
 * @ORM\Entity(repositoryClass="Oro\Bundle\UPSBundle\Entity\Repository\ShippingServiceRepository")
 */
class ShippingService
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=10)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    protected $description;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AddressBundle\Entity\Country")
     * @ORM\JoinColumn(name="country_code", referencedColumnName="iso2_code", nullable=false)
     */
    protected $country;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }


    /**
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param Country $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    public function __toString()
    {
        return (string) $this->getDescription();
    }
}
