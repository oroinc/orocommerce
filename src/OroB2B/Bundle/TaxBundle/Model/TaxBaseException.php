<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

class TaxBaseException
{
    const USE_AS_BASE_SHIPPING_ORIGIN = 'shipping_origin';
    const USE_AS_BASE_DESTINATION = 'destination';

    /**
     * @var Country
     */
    protected $country;

    /**
     * @var Region
     */
    protected $region;

    /**
     * @var string
     */
    protected $option;


    /**
     * Set country
     *
     * @param Country $country
     * @return TaxBaseException
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set region
     *
     * @param Region $region
     * @return TaxBaseException
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set option
     *
     * @param string $option
     * @return TaxBaseException
     */
    public function setOption($option)
    {
        $this->option = $option;

        return $this;
    }

    /**
     * Get option
     *
     * @return string
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * Get Region text
     *
     * @return null
     */
    public function getRegionText()
    {
        return null;
    }
}
