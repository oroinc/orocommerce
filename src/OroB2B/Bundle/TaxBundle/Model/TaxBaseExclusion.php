<?php

namespace Oro\Bundle\TaxBundle\Model;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class TaxBaseExclusion extends AbstractResult
{
    const COUNTRY = 'country';
    const REGION = 'region';
    const OPTION = 'option';

    /**
     * @param Country $country
     * @return TaxBaseExclusion
     */
    public function setCountry($country)
    {
        $this->offsetSet(self::COUNTRY, $country);

        return $this;
    }

    /**
     * Get country
     *
     * @return Country
     */
    public function getCountry()
    {
        return $this->getOffset(self::COUNTRY);
    }

    /**
     * Set region
     *
     * @param Region $region
     * @return TaxBaseExclusion
     */
    public function setRegion($region)
    {
        $this->offsetSet(self::REGION, $region);

        return $this;
    }

    /**
     * Get region
     *
     * @return Region
     */
    public function getRegion()
    {
        return $this->getOffset(self::REGION);
    }

    /**
     * Set option
     *
     * @param string $option
     * @return TaxBaseExclusion
     */
    public function setOption($option)
    {
        $options = [
            TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
            TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
        ];

        if (!in_array($option, $options, true)) {
            throw new \InvalidArgumentException(
                sprintf('Option values is "%s", one of "%s" allowed', $option, implode(',', $options))
            );
        }

        $this->offsetSet(self::OPTION, $option);

        return $this;
    }

    /**
     * Get option
     *
     * @return string
     */
    public function getOption()
    {
        return $this->getOffset(self::OPTION);
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
