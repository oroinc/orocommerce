<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;

/**
 * Product unit entity.
 */
#[ORM\Entity(repositoryClass: ProductUnitRepository::class)]
#[ORM\Table(name: 'oro_product_unit')]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-briefcase'], 'dataaudit' => ['auditable' => true]])]
class ProductUnit implements MeasureUnitInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $code = null;

    #[ORM\Column(name: 'default_precision', type: Types::INTEGER)]
    protected ?int $defaultPrecision = null;

    /**
     * Set code
     *
     * @param string $code
     * @return ProductUnit
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    #[\Override]
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set defaultPrecision
     *
     * @param integer $defaultPrecision
     * @return ProductUnit
     */
    public function setDefaultPrecision($defaultPrecision)
    {
        $this->defaultPrecision = $defaultPrecision;

        return $this;
    }

    /**
     * Get defaultPrecision
     *
     * @return integer
     */
    public function getDefaultPrecision()
    {
        return $this->defaultPrecision;
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
