<?php

namespace OroB2B\Bundle\CatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;

/**
 * @ORM\Table(name="orob2b_category_def_prod_opts")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class CategoryDefaultProductOptions
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="product_unit_code", referencedColumnName="code", onDelete="CASCADE")
     */
    protected $unit;

    /**
     * @var integer
     *
     * @ORM\Column(name="product_unit_precision", nullable=true, type="integer")
     */
    protected $precision;

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
     * @param CategoryUnitPrecision $unitPrecision
     *
     * @return Category
     */
    public function setUnitPrecision(CategoryUnitPrecision $unitPrecision = null)
    {
        $this->unitPrecision = $unitPrecision;
        $this->updateUnitPrecision();

        return $this;
    }

    /**
     * @ORM\PostLoad
     */
    public function loadUnitPrecision()
    {
        $this->unitPrecision = CategoryUnitPrecision::create($this->precision, $this->unit);
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
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
    public function __toString()
    {
        return (string)$this->getId();
    }
}
