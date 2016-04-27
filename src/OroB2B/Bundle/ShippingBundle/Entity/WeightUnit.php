<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField; // required by DatesAwareTrait

/**
 * @ORM\Table(name="orob2b_shipping_weight_unit")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class WeightUnit implements DatesAwareInterface
{
    use DatesAwareTrait;

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
     * @ORM\Column(name="code", type="string", length=255, unique=true, nullable=false)
     */
    protected $code;

    /**
     * @var array
     *
     * @ORM\Column(name="conversion_rates", type="array", nullable=true)
     */
    protected $conversionRates = [];

    /**
     * @param int $id
     *
     * @return WeightUnit
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $code
     *
     * @return WeightUnit
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param array $conversionRates
     *
     * @return WeightUnit
     */
    public function setConversionRates(array $conversionRates = [])
    {
        $this->conversionRates = $conversionRates;

        return $this;
    }

    /**
     * @return array
     */
    public function getConversionRates()
    {
        return $this->conversionRates;
    }
}
