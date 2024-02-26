<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

/**
* Entity that represents Weight Unit
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_shipping_weight_unit')]
class WeightUnit implements MeasureUnitInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(name: 'code', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $code = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'conversion_rates', type: Types::ARRAY, nullable: true)]
    protected $conversionRates = [];

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

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->code;
    }
}
