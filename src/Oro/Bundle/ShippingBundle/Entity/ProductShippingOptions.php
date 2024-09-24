<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

/**
* Entity that represents Product Shipping Options
*
*/
#[ORM\Entity(repositoryClass: ProductShippingOptionsRepository::class)]
#[ORM\Table(name: 'oro_shipping_product_opts')]
#[ORM\UniqueConstraint(name: 'oro_shipping_product_opts_uidx', columns: ['product_id', 'product_unit_code'])]
#[ORM\HasLifecycleCallbacks]
class ProductShippingOptions implements
    ProductShippingOptionsInterface,
    ProductUnitHolderInterface,
    ProductHolderInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'product_unit_code', referencedColumnName: 'code', nullable: false, onDelete: 'CASCADE')]
    protected ?ProductUnit $productUnit = null;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'weight_value', type: Types::FLOAT, nullable: true)]
    protected $weightValue;

    #[ORM\ManyToOne(targetEntity: WeightUnit::class)]
    #[ORM\JoinColumn(name: 'weight_unit_code', referencedColumnName: 'code')]
    protected ?WeightUnit $weightUnit = null;

    /**
     * @var Weight
     */
    protected $weight;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'dimensions_length', type: Types::FLOAT, nullable: true)]
    protected $dimensionsLength;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'dimensions_width', type: Types::FLOAT, nullable: true)]
    protected $dimensionsWidth;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'dimensions_height', type: Types::FLOAT, nullable: true)]
    protected $dimensionsHeight;

    #[ORM\ManyToOne(targetEntity: LengthUnit::class)]
    #[ORM\JoinColumn(name: 'dimensions_unit_code', referencedColumnName: 'code')]
    protected ?LengthUnit $dimensionsUnit = null;

    /**
     * @var Dimensions
     */
    protected $dimensions;

    #[ORM\ManyToOne(targetEntity: FreightClass::class)]
    #[ORM\JoinColumn(name: 'freight_class_code', referencedColumnName: 'code')]
    protected ?FreightClass $freightClass = null;

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
    #[\Override]
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product|null $product
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
    #[\Override]
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * @param ProductUnit|null $productUnit
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
    #[\Override]
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param Weight|null $weight
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
    #[\Override]
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @param Dimensions|null $dimensions
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
     * @param FreightClass|null $freightClass
     * @return $this
     */
    public function setFreightClass(FreightClass $freightClass = null)
    {
        $this->freightClass = $freightClass;

        return $this;
    }

    #[\Override]
    public function getEntityIdentifier()
    {
        return $this->getId();
    }

    #[\Override]
    public function getProductHolder()
    {
        return $this;
    }

    #[\Override]
    public function getProductUnitCode()
    {
        return $this->getProductUnit()->getCode();
    }

    #[\Override]
    public function getProductSku()
    {
        return $this->getProduct()->getSku();
    }

    #[ORM\PostLoad]
    public function loadWeight()
    {
        $this->weight = Weight::create($this->weightValue, $this->weightUnit);
    }

    #[ORM\PostLoad]
    public function loadDimensions()
    {
        $this->dimensions = Dimensions::create(
            $this->dimensionsLength,
            $this->dimensionsWidth,
            $this->dimensionsHeight,
            $this->dimensionsUnit
        );
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
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

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
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
