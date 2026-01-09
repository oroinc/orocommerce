<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Component\DoctrineUtils\ORM\Id\UuidGenerator;

/**
 * Base entity class for product price entities.
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
class BaseProductPrice implements
    ProductUnitHolderInterface,
    ProductHolderInterface,
    PriceAwareInterface,
    ProductPriceInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'id', type: Types::GUID)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected $id;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 10, 'identity' => true]])]
    protected ?Product $product = null;

    #[ORM\Column(name: 'product_sku', type: Types::STRING, length: 255)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?string $productSku = null;

    /**
     * @var BasePriceList
     **/
    protected $priceList;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'quantity', type: Types::FLOAT)]
    #[ConfigField(
        defaultValues: ['importexport' => ['order' => 20, 'identity' => true], 'dataaudit' => ['auditable' => true]]
    )]
    protected $quantity;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'unit_code', referencedColumnName: 'code', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(
        defaultValues: ['importexport' => ['order' => 30, 'identity' => true], 'dataaudit' => ['auditable' => true]]
    )]
    protected ?ProductUnit $unit = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'value', type: 'money')]
    #[ConfigField(
        defaultValues: ['importexport' => ['order' => 40, 'header' => 'Price'], 'dataaudit' => ['auditable' => true]]
    )]
    protected $value;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3)]
    #[ConfigField(
        defaultValues: ['importexport' => ['order' => 50, 'identity' => true], 'dataaudit' => ['auditable' => true]]
    )]
    protected ?string $currency = null;

    /**
     * Changes to this value object wont affect entity change set
     * To change persisted price value you should create and set new Price
     *
     * @var Price
     */
    protected $price;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    #[\Override]
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
        $this->productSku = $product->getSku();

        return $this;
    }

    #[\Override]
    public function getProductSku()
    {
        return $this->productSku;
    }

    /**
     * @param BasePriceList $priceList
     * @return $this
     */
    public function setPriceList(BasePriceList $priceList)
    {
        $this->priceList = $priceList;

        return $this;
    }

    /**
     * @return BasePriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return float
     */
    #[\Override]
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return ProductUnit
     */
    #[\Override]
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param ProductUnit $unit
     * @return $this
     */
    public function setUnit(ProductUnit $unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @param Price $price
     * @return $this
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;
        $this->updatePrice();

        return $this;
    }

    /**
     * @return Price|null
     */
    #[\Override]
    public function getPrice()
    {
        if (null === $this->price) {
            $this->loadPrice();
        }

        return $this->price;
    }

    #[ORM\PostLoad]
    public function loadPrice()
    {
        if (null !== $this->value && null !== $this->currency) {
            $this->price = Price::create($this->value, $this->currency);
        } else {
            $this->price = null;
        }

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatePrice()
    {
        if ($this->price) {
            $this->value = $this->price->getValue();
            $this->currency = $this->price->getCurrency();
        }
    }

    #[\Override]
    public function getEntityIdentifier()
    {
        return $this->id;
    }

    #[\Override]
    public function getProductHolder()
    {
        return $this;
    }

    #[\Override]
    public function getProductUnit()
    {
        return $this->getUnit();
    }

    #[\Override]
    public function getProductUnitCode()
    {
        return $this->getUnit()->getCode();
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function __clone()
    {
        $this->id = null;
    }
}
