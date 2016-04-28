<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @ORM\Table(
 *      name="orob2b_shipping_prod_unit_opts",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="shipping_product_unit_options__product_id__unit_code__uidx",
 *              columns={"product_id","unit_code"}
 *          )
 *      }
 * )
 * @ORM\Entity
 * @Config(mode="hidden")
 */
class ProductShippingOptions implements DatesAwareInterface
{
    use DatesAwareTrait;

    /**
     * @var integer
     *
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
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
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
     * @var ProductUnit
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="unit_code", referencedColumnName="code", nullable=false, onDelete="CASCADE")
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
     * @var WeightUnit
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\WeightUnit")
     * @ORM\JoinColumn(name="weght_unit_code", referencedColumnName="code", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=20,
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $weightUnit;

    /**
     * @var float
     *
     * @ORM\Column(name="weight",type="float")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=21
     *          }
     *      }
     * )
     */
    protected $weight;

    /**
     * @var LengthUnit
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\LengthUnit")
     * @ORM\JoinColumn(name="length_unit_code", referencedColumnName="code", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=30,
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $lengthUnit;

    /**
     * @var float
     *
     * @ORM\Column(name="length",type="float")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=31
     *          }
     *      }
     * )
     */
    protected $length;

    /**
     * @var float
     *
     * @ORM\Column(name="width",type="float")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=32
     *          }
     *      }
     * )
     */
    protected $width;

    /**
     * @var float
     *
     * @ORM\Column(name="height",type="float")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=33
     *          }
     *      }
     * )
     */
    protected $height;

    /**
     * @var FreightClass
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\FreightClass")
     * @ORM\JoinColumn(name="freight_class_code", referencedColumnName="code", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=40,
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $freightClass;

    /**
     * @param FreightClass $freightClass
     *
     * @return $this
     */
    public function setFreightClass($freightClass)
    {
        $this->freightClass = $freightClass;

        return $this;
    }

    /**
     * @return FreightClass
     */
    public function getFreightClass()
    {
        return $this->freightClass;
    }

    /**
     * @param float $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $id
     *
     * @return $this
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
     * @param float $length
     *
     * @return $this
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @return float
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param LengthUnit $lengthUnit
     *
     * @return $this
     */
    public function setLengthUnit(LengthUnit $lengthUnit)
    {
        $this->lengthUnit = $lengthUnit;

        return $this;
    }

    /**
     * @return LengthUnit
     */
    public function getLengthUnit()
    {
        return $this->lengthUnit;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param ProductUnit $unit
     *
     * @return $this
     */
    public function setUnit(ProductUnit $unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return ProductUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param float $weight
     *
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param WeightUnit $weightUnit
     *
     * @return $this
     */
    public function setWeightUnit(WeightUnit $weightUnit)
    {
        $this->weightUnit = $weightUnit;

        return $this;
    }

    /**
     * @return WeightUnit
     */
    public function getWeightUnit()
    {
        return $this->weightUnit;
    }

    /**
     * @param float $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }
}
