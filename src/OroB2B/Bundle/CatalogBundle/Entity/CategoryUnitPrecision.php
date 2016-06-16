<?php

namespace OroB2B\Bundle\CatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

/**
 * @ORM\Table(name="orob2b_category_unit_precision")
 * @ORM\Entity
 * @Config(mode="hidden")
 */
class CategoryUnitPrecision implements ProductUnitHolderInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="unit_code", referencedColumnName="code", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=10,
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $unit;

    /**
     * @var integer
     *
     * @ORM\Column(name="unit_precision",type="integer")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=20
     *          }
     *      }
     * )
     */
    protected $precision;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set precision
     *
     * @param integer $precision
     *
     * @return CategoryUnitPrecision
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
    
        return $this;
    }

    /**
     * Get precision
     *
     * @return integer
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * Set unit
     *
     * @param ProductUnit $unit
     *
     * @return CategoryUnitPrecision
     */
    public function setUnit(ProductUnit $unit = null)
    {
        $this->unit = $unit;
    
        return $this;
    }

    /**
     * Get unit
     *
     * @return ProductUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityIdentifier()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductHolder()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnit()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnitCode()
    {
        return $this->getUnit()->getCode();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUnit()->getCode() . ' ' . $this->getPrecision();
    }
}
