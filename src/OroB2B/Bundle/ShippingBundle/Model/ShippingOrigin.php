<?php

namespace OroB2B\Bundle\ShippingBundle\Model;

use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class ShippingOrigin extends AbstractAddress
{
    /** @var \ArrayObject */
    protected $data;

    /** @var bool */
    protected $system = true;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = new \ArrayObject();

        foreach ($data as $name => $value) {
            $method = Inflector::camelize('set' . ucfirst($name));

            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /** {@inheritdoc} */
    public function setCountry($country)
    {
        $this->data->offsetSet('country', $country);

        return parent::setCountry($country);
    }

    /** {@inheritdoc} */
    public function getCountry()
    {
        return $this->getOffset('country');
    }

    /** {@inheritdoc} */
    public function setRegion($region)
    {
        $this->data->offsetSet('region', $region);

        return parent::setRegion($region);
    }

    /** {@inheritdoc} */
    public function getRegion()
    {
        return $this->getOffset('region');
    }

    /** {@inheritdoc} */
    public function setRegionText($regionText)
    {
        $this->data->offsetSet('region_text', $regionText);

        return parent::setRegionText($regionText);
    }

    /** {@inheritdoc} */
    public function getRegionText()
    {
        return $this->getOffset('region_text');
    }

    /** {@inheritdoc} */
    public function setPostalCode($postalCode)
    {
        $this->data->offsetSet('postalCode', $postalCode);

        return parent::setPostalCode($postalCode);
    }

    /** {@inheritdoc} */
    public function getPostalCode()
    {
        return $this->getOffset('postalCode');
    }

    /** {@inheritdoc} */
    public function setCity($city)
    {
        $this->data->offsetSet('city', $city);

        return parent::setCity($city);
    }

    /** {@inheritdoc} */
    public function getCity()
    {
        return $this->getOffset('city');
    }

    /** {@inheritdoc} */
    public function setStreet($street)
    {
        $this->data->offsetSet('street', $street);

        return parent::setStreet($street);
    }

    /** {@inheritdoc} */
    public function getStreet()
    {
        return $this->getOffset('street');
    }

    /** {@inheritdoc} */
    public function setStreet2($street2)
    {
        $this->data->offsetSet('street2', $street2);

        return parent::setStreet2($street2);
    }

    /** {@inheritdoc} */
    public function getStreet2()
    {
        return $this->getOffset('street2');
    }

    /**
     * @param string $offset
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getOffset($offset, $default = null)
    {
        if ($this->data->offsetExists((string)$offset)) {
            return $this->data->offsetGet((string)$offset);
        }

        return $default;
    }

    /**
     * @return boolean
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * @param bool $system
     *
     * @return $this
     */
    public function setSystem($system)
    {
        $this->system = (bool)$system;

        return $this;
    }
}
