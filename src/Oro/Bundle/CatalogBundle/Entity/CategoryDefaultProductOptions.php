<?php

namespace Oro\Bundle\CatalogBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
* Entity that represents Category Default Product Options
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_category_def_prod_opts')]
#[ORM\HasLifecycleCallbacks]
class CategoryDefaultProductOptions
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'product_unit_code', referencedColumnName: 'code', onDelete: 'CASCADE')]
    protected ?ProductUnit $unit = null;

    #[ORM\Column(name: 'product_unit_precision', type: Types::INTEGER, nullable: true)]
    protected ?int $precision = null;

    /**
     * @var CategoryUnitPrecision
     */
    protected $unitPrecision;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CategoryUnitPrecision
     */
    public function getUnitPrecision()
    {
        return $this->unitPrecision;
    }

    /**
     * @param CategoryUnitPrecision|null $unitPrecision
     *
     * @return Category
     */
    public function setUnitPrecision(CategoryUnitPrecision $unitPrecision = null)
    {
        $this->unitPrecision = $unitPrecision;
        $this->updateUnitPrecision();

        return $this;
    }

    #[ORM\PostLoad]
    public function loadUnitPrecision()
    {
        $this->unitPrecision = CategoryUnitPrecision::create($this->precision, $this->unit);
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateUnitPrecision()
    {
        if ($this->unitPrecision) {
            $this->precision = $this->unitPrecision->getPrecision();
            $this->unit = $this->unitPrecision->getUnit();
        } else {
            $this->precision = null;
            $this->unit = null;
        }
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getId();
    }
}
