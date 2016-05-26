<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
 */
class ProductShippingOptions implements
    ProductShippingOptionsInterface,
    ProductUnitHolderInterface,
    ProductHolderInterface
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
     * @ORM\Column(name="weight_value", type="float", nullable=true)
     */
    protected $weightValue;

    /**
     * @var WeightUnit
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\WeightUnit")
     * @ORM\JoinColumn(name="weight_unit_code", referencedColumnName="code")
     */
    protected $weightUnit;

    /**
     * @var Weight
     */
    protected $weight;

    /**
     * @var float
     *
     * @ORM\Column(name="dimensions_length", type="float", nullable=true)
     */
    protected $dimensionsLength;

    /**
     * @var float
     *
     * @ORM\Column(name="dimensions_width", type="float", nullable=true)
     */
    protected $dimensionsWidth;

    /**
     * @var float
     *
     * @ORM\Column(name="dimensions_height", type="float", nullable=true)
     */
    protected $dimensionsHeight;

    /**
     * @var LengthUnit
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ShippingBundle\Entity\LengthUnit")
     * @ORM\JoinColumn(name="dimensions_unit_code", referencedColumnName="code")
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
     * @ORM\JoinColumn(name="freight_class_code", referencedColumnName="code")
     */
    protected $freightClass;

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
        $this->updateWeight();

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
        $this->updateDimensions();

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
        } else {
            $this->weightValue = null;
            $this->weightUnit = null;
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateDimensions()
    {
        if ($this->dimensions && $this->dimensions->getValue()) {
            $this->dimensionsLength = $this->dimensions->getValue()->getLength();
            $this->dimensionsWidth = $this->dimensions->getValue()->getWidth();
            $this->dimensionsHeight = $this->dimensions->getValue()->getHeight();
            $this->dimensionsUnit = $this->dimensions->getUnit();
        } else {
            $this->dimensionsLength = null;
            $this->dimensionsWidth = null;
            $this->dimensionsHeight = null;
            $this->dimensionsUnit = null;
        }
    }
}
