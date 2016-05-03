<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use OroB2B\Bundle\ShippingBundle\Model\Dimensions;
use OroB2B\Bundle\ShippingBundle\Model\Weight;

/**
 * @ORM\Table(
 *      name="orob2b_shipping_product_opts",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="shipping_product_opts__product_id__product_unit_code__uidx",
 *              columns={"product_id","product_unit_code"}
 *          )
 *      }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @Config(mode="hidden")
 */
class ProductShippingOptions implements ProductUnitHolderInterface, ProductHolderInterface
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var ProductUnit
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="product_unit_code", referencedColumnName="code", nullable=false, onDelete="CASCADE")
     */
    protected $productUnit;

    /**
     * @var float
     *
     * @ORM\Column(name="weight_value", type="float")
     */
    protected $weightValue;

    /**
     * @var WeightUnit
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\WeightUnit")
     * @ORM\JoinColumn(name="weight_unit_code", referencedColumnName="code", nullable=false, onDelete="CASCADE")
     */
    protected $weightUnit;

    /**
     * @var Weight
     */
    protected $weight;

    /**
     * @var float
     *
     * @ORM\Column(name="dimensions_length",type="float")
     */
    protected $dimensionsLength;

    /**
     * @var float
     *
     * @ORM\Column(name="dimensions_width",type="float")
     */
    protected $dimensionsWidth;

    /**
     * @var float
     *
     * @ORM\Column(name="dimensions_height",type="float")
     */
    protected $dimensionsHeight;

    /**
     * @var LengthUnit
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\LengthUnit")
     * @ORM\JoinColumn(name="dimensions_unit_code", referencedColumnName="code", nullable=false, onDelete="CASCADE")
     */
    protected $dimensionsUnit;

    /**
     * @var Dimensions
     */
    protected $dimensions;

    /**
     * @var FreightClass
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\FreightClass")
     * @ORM\JoinColumn(name="freight_class_code", referencedColumnName="code", nullable=false, onDelete="CASCADE")
     */
    protected $freightClass;

    /**
     * {@inheritdoc}
     */
    public function getEntityIdentifier()
    {
        return $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductHolder()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnitCode()
    {
        return $this->getProductUnit()->getCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductSku()
    {
        return $this->getProduct()->getSku();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * @param ProductUnit $productUnit
     *
     * @return $this
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    /**
     * @return Weight
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param Weight $weight
     *
     * @return $this
     */
    public function setWeight(Weight $weight = null)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return Dimensions
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @param Dimensions $dimensions
     *
     * @return $this
     */
    public function setDimensions(Dimensions $dimensions = null)
    {
        $this->dimensions = $dimensions;

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
     * @param FreightClass $freightClass
     * @return $this
     */
    public function setFreightClass(FreightClass $freightClass = null)
    {
        $this->freightClass = $freightClass;

        return $this;
    }

    /**
     * @ORM\PostLoad
     */
    public function loadWeight()
    {
        $this->weight = Weight::create($this->weightValue, $this->weightUnit);
    }

    /**
     * @ORM\PostLoad
     */
    public function loadDimensions()
    {
        $this->dimensions = Dimensions::create(
            $this->dimensionsLength,
            $this->dimensionsWidth,
            $this->dimensionsHeight,
            $this->dimensionsUnit
        );
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateWeight()
    {
        if ($this->weight) {
            $this->weightValue = $this->weight->getValue();
            $this->weightUnit = $this->weight->getUnit();
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateDimensions()
    {
        if ($this->dimensions) {
            $this->dimensionsLength = $this->dimensions->getLength();
            $this->dimensionsWidth = $this->dimensions->getWidth();
            $this->dimensionsHeight = $this->dimensions->getHeight();
            $this->dimensionsUnit = $this->dimensions->getUnit();
        }
    }
}
