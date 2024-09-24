<?php

namespace Oro\Bundle\ShippingBundle\Model;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Component\DoctrineUtils\Inflector\InflectorFactory;

/**
 * The base class for shipping address entity.
 */
class ShippingOrigin extends AbstractAddress
{
    /** @var \ArrayObject */
    protected $data;

    /** @var bool */
    protected $system = true;

    public function __construct(array $data = [])
    {
        $this->data = new \ArrayObject();

        foreach ($data as $name => $value) {
            $method = InflectorFactory::create()->camelize('set' . ucfirst($name));

            if (EntityPropertyInfo::methodExists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    #[\Override]
    public function setCountry($country)
    {
        $this->data->offsetSet('country', $country);

        return parent::setCountry($country);
    }

    #[\Override]
    public function getCountry()
    {
        return $this->getOffset('country');
    }

    #[\Override]
    public function setRegion($region)
    {
        $this->data->offsetSet('region', $region);

        return parent::setRegion($region);
    }

    #[\Override]
    public function getRegion()
    {
        return $this->getOffset('region');
    }

    #[\Override]
    public function setRegionText($regionText)
    {
        $this->data->offsetSet('region_text', $regionText);

        return parent::setRegionText($regionText);
    }

    #[\Override]
    public function getRegionText()
    {
        return $this->getOffset('region_text');
    }

    #[\Override]
    public function setPostalCode($postalCode)
    {
        $this->data->offsetSet('postalCode', $postalCode);

        return parent::setPostalCode($postalCode);
    }

    #[\Override]
    public function getPostalCode()
    {
        return $this->getOffset('postalCode');
    }

    #[\Override]
    public function setCity($city)
    {
        $this->data->offsetSet('city', $city);

        return parent::setCity($city);
    }

    #[\Override]
    public function getCity()
    {
        return $this->getOffset('city');
    }

    #[\Override]
    public function setStreet($street)
    {
        $this->data->offsetSet('street', $street);

        return parent::setStreet($street);
    }

    #[\Override]
    public function getStreet()
    {
        return $this->getOffset('street');
    }

    #[\Override]
    public function setStreet2($street2)
    {
        $this->data->offsetSet('street2', $street2);

        return parent::setStreet2($street2);
    }

    #[\Override]
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
