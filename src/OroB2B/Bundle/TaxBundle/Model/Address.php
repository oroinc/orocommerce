<?php

namespace Oro\Bundle\TaxBundle\Model;

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
        $this->data->offsetSet('region', $region);

        return parent::setRegion($region);
    }

    /** {@inheritdoc} */
    public function setRegionText($regionText)
    {
        $this->data->offsetSet('region_text', $regionText);

        return parent::setRegionText($regionText);
    }

    /** {@inheritdoc} */
    public function setPostalCode($postalCode)
    {
        $this->data->offsetSet('postal_code', $postalCode);

        return parent::setPostalCode($postalCode);
    }

    /** {@inheritdoc} */
    public function setCountry($country)
    {
        $this->data->offsetSet('country', $country);

        return parent::setCountry($country);
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
