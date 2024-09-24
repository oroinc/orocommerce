<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

/**
* Entity that represents Freight Class
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_shipping_freight_class')]
#[Config]
class FreightClass implements MeasureUnitInterface, FreightClassInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(name: 'code', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $code = null;

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
    #[\Override]
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->code;
    }
}
