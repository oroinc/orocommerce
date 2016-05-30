<?php

namespace OroB2B\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

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
 * @Config(mode="hidden")
 */
class ProductUnitPrecision implements ProductUnitHolderInterface
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
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="unitPrecisions")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $product;

    /**
     * @ORM\ManyToOne(targetEntity="ProductUnit")
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
     * @var float
     *
     * @ORM\Column(name="conversion_rate",type="float",nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=30
     *          }
     *      }
     * )
     */
    protected $conversionRate;

    /**
     * @var bool
     *
     * @ORM\Column(name="sell",type="boolean",nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=40
     *          }
     *      }
     * )
     */
    protected $sell;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

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

    /**
     * @param float $conversionRate
     * @return ProductUnitPrecision
     */
    public function setConversionRate($conversionRate)
    {
        $this->conversionRate = $conversionRate;
        
        return $this;
    }
    
    /**
     * @return float
     */
    public function getConversionRate()
    {
        return $this->conversionRate;
    }

    /**
     * @return boolean
     */
    public function isSell()
    {
        return $this->sell;
    }

    /**
     * @param boolean $sell
     * @return ProductUnitPrecision
     */
    public function setSell($sell)
    {
        $this->sell = $sell;
        
        return $this;
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
