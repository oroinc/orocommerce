<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

/**
 * @ORM\Table(name="oro_shipping_freight_class")
 * @ORM\Entity
 * @Config
 */
class FreightClass implements MeasureUnitInterface, FreightClassInterface
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(name="code", type="string", length=255, nullable=false)
     */
    protected $code;

    /**
     * @param string $code
     *
     * @return FreightClass
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
     * @return string
     */
    public function __toString()
    {
        return (string)$this->code;
    }
}
