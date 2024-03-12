<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

/**
* Entity that represents Product Unit Precision
*
*/
#[ORM\Entity(repositoryClass: ProductUnitPrecisionRepository::class)]
#[ORM\Table(name: 'oro_product_unit_precision')]
#[ORM\UniqueConstraint(name: 'uidx_oro_product_unit_precision', columns: ['product_id', 'unit_code'])]
#[Config]
class ProductUnitPrecision implements ProductUnitHolderInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'unitPrecisions')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'unit_code', referencedColumnName: 'code', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 10, 'identity' => true]])]
    protected ?ProductUnit $unit = null;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'unit_precision', type: Types::INTEGER)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 20]])]
    protected $precision;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'conversion_rate', type: Types::FLOAT, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 30]])]
    protected $conversionRate;

    #[ORM\Column(name: 'sell', type: Types::BOOLEAN, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 40]])]
    protected ?bool $sell = true;

    public function __clone()
    {
        $this->id = null;
    }

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
     * @param Product|null $product
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
     * @param ProductUnit|null $unit
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
     * @param int|null $precision
     * @return ProductUnitPrecision
     */
    public function setPrecision(int $precision = null)
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
        if ($unit = $this->getUnit()) {
            return $unit->getCode();
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->getUnit()) {
            return $this->getUnit()->getCode() . ' ' . $this->getPrecision();
        }

        return '';
    }
}
