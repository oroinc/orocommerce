<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class Address extends AbstractAddress
{
    /** @var \ArrayObject */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = new \ArrayObject($data);
    }

    /** {@inheritdoc} */
    public function setRegion($region)
    {
        parent::setRegion($region);

        $this->data->offsetSet('region', $region);
    }

    /** {@inheritdoc} */
    public function setRegionText($regionText)
    {
        parent::setRegionText($regionText);

        $this->data->offsetSet('region_text', $regionText);
    }

    /** {@inheritdoc} */
    public function setPostalCode($postalCode)
    {
        parent::setPostalCode($postalCode);

        $this->data->offsetSet('postal_code', $postalCode);
    }

    /** {@inheritdoc} */
    public function setCountry($country)
    {
        parent::setCountry($country);

        $this->data->offsetSet('country', $country);
    }

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function getRegionText()
    {
        return $this->getOffset('region_text');
    }

    /** {@inheritdoc} */
    public function getPostalCode()
    {
        return $this->getOffset('postal_code');
    }

    /** {@inheritdoc} */
    public function getCountry()
    {
        return $this->getOffset('country');
    }
}
