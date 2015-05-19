<?php

namespace OroB2B\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *      name="orob2b_product_unit_precision",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="product_unit_precision__product_id__unit_code__uidx",
 *              columns={"product_id","unit_code"}
 *          )
 *      }
 * )
 * @ORM\Entity
 */
class ProductUnitPrecision
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="unitPrecisions")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    protected $product;

    /**
     * @ORM\ManyToOne(targetEntity="ProductUnit")
     * @ORM\JoinColumn(name="unit_code", referencedColumnName="code")
     */
    protected $unit;

    /**
     * @var integer
     *
     * @ORM\Column(name="unit_precision",type="integer")
     */
    protected $precision;

    /**
     * Set product
     *
     * @param Product $product
     * @return ProductUnitPrecision
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set product unit
     *
     * @param ProductUnit $unit
     * @return ProductUnitPrecision
     */
    public function setUnit(ProductUnit $unit = null)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get product unit
     *
     * @return ProductUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set precision
     *
     * @param integer $precision
     * @return ProductUnitPrecision
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
}
