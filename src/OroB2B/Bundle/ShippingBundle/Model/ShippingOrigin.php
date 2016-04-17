<?php

namespace OroB2B\Bundle\ShippingBundle\Model;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

class ShippingOrigin
{
    /** @var Country */
    private $country;

    /** @var Region */
    private $region;

    /** @var string */
    private $postalCode;

    /** @var string */
    private $city;

    /** @var string */
    private $street;

    /** @var string */
    private $street2;

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
    public function setCountry(Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param Region $region
     * @return $this
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     * @return $this
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     * @return $this
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * @return string
     */
    public function getStreet2()
    {
        return $this->street2;
    }

    /**
     * @param string $street2
     * @return $this
     */
    public function setStreet2($street2)
    {
        $this->street2 = $street2;

        return $this;
    }
}
