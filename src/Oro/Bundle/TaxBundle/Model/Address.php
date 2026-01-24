<?php

namespace Oro\Bundle\TaxBundle\Model;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

/**
 * Address model used for tax calculation purposes.
 *
 * This class extends the base address functionality to provide a flexible data container for address information
 * used in tax calculations. It stores address components in an internal {@see \ArrayObject},
 * allowing for dynamic access to address data while maintaining compatibility with the standard address interface.
 * This is particularly useful when working with addresses from various sources during tax resolution.
 */
class Address extends AbstractAddress
{
    /** @var \ArrayObject */
    protected $data;

    public function __construct(array $data = [])
    {
        $this->data = new \ArrayObject($data);
    }

    #[\Override]
    public function setRegion($region)
    {
        $this->data->offsetSet('region', $region);

        return parent::setRegion($region);
    }

    #[\Override]
    public function setRegionText($regionText)
    {
        $this->data->offsetSet('region_text', $regionText);

        return parent::setRegionText($regionText);
    }

    #[\Override]
    public function setPostalCode($postalCode)
    {
        $this->data->offsetSet('postal_code', $postalCode);

        return parent::setPostalCode($postalCode);
    }

    #[\Override]
    public function setCountry($country)
    {
        $this->data->offsetSet('country', $country);

        return parent::setCountry($country);
    }

    #[\Override]
    public function getRegion()
    {
        return $this->getOffset('region');
    }

    /**
     * @param string $offset
     * @param mixed $default
     * @return mixed
     */
    protected function getOffset($offset, $default = null)
    {
        if ($this->data->offsetExists((string)$offset)) {
            return $this->data->offsetGet((string)$offset);
        }

        return $default;
    }

    #[\Override]
    public function getRegionText()
    {
        return $this->getOffset('region_text');
    }

    #[\Override]
    public function getPostalCode()
    {
        return $this->getOffset('postal_code');
    }

    #[\Override]
    public function getCountry()
    {
        return $this->getOffset('country');
    }
}
