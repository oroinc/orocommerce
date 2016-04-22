<?php

namespace OroB2B\Bundle\ShippingBundle\Model;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class ShippingOrigin extends AbstractAddress
{
    /** @var \ArrayObject */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = new \ArrayObject($data);

        if (!empty($data['city'])) {
            $this->setCity($data['city']);
        }
        if (!empty($data['street'])) {
            $this->setStreet($data['street']);
        }
        if (!empty($data['street2'])) {
            $this->setStreet2($data['street2']);
        }
        if (!empty($data['postal_code'])) {
            $this->setPostalCode($data['postal_code']);
        }
        if (!empty($data['region_text'])) {
            $this->setRegionText($data['region_text']);
        }
    }

    /** {@inheritdoc} */
    public function setCountry($country)
    {
        $this->data->offsetSet('country', $country);

        return parent::setCountry($country);
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
    public function setCity($city)
    {
        $this->data->offsetSet('city', $city);

        return parent::setCity($city);
    }

    /** {@inheritdoc} */
    public function setStreet($street)
    {
        $this->data->offsetSet('street', $street);

        return parent::setStreet($street);
    }

    /** {@inheritdoc} */
    public function setStreet2($street2)
    {
        $this->data->offsetSet('street2', $street2);

        return parent::setStreet2($street2);
    }

    /** {@inheritdoc} */
    public function getCountry()
    {
        return $this->getOffset('country');
    }

    /** {@inheritdoc} */
    public function getRegion()
    {
        return $this->getOffset('region');
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
    public function getCity()
    {
        return $this->getOffset('city');
    }

    /** {@inheritdoc} */
    public function getStreet()
    {
        return $this->getOffset('street');
    }

    /** {@inheritdoc} */
    public function getStreet2()
    {
        return $this->getOffset('street2');
    }

    /**
     * @param string $offset
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getOffset($offset, $default = null)
    {
        if ($this->data->offsetExists((string) $offset)) {
            return $this->data->offsetGet((string) $offset);
        }

        return $default;
    }
}
