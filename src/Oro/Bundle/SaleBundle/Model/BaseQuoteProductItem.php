<?php

namespace Oro\Bundle\SaleBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemChecksumAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;

/**
 * Model contains information about quote product
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-list-alt'], 'security' => ['type' => 'ACL', 'group_name' => '']])]
class BaseQuoteProductItem implements
    ProductLineItemInterface,
    ProductLineItemChecksumAwareInterface,
    ProductKitItemLineItemsAwareInterface,
    PriceAwareInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var QuoteProduct
     */
    #[ORM\ManyToOne(targetEntity: 'Oro\Bundle\SaleBundle\Entity\QuoteProduct')]
    #[ORM\JoinColumn(name: 'quote_product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?QuoteProduct $quoteProduct = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'quantity', type: 'float', nullable: true)]
    protected $quantity;

    /**
     * @var ProductUnit
     */
    #[ORM\ManyToOne(targetEntity: 'Oro\Bundle\ProductBundle\Entity\ProductUnit')]
    #[ORM\JoinColumn(name: 'product_unit_id', referencedColumnName: 'code', onDelete: 'SET NULL')]
    protected $productUnit;

    /**
     * @var string
     */
    #[ORM\Column(name: 'product_unit_code', type: 'string', length: 255)]
    protected $productUnitCode;

    /**
     * @var float
     */
    #[ORM\Column(name: 'value', type: 'money', nullable: true)]
    protected $value;

    /**
     * @var string
     */
    #[ORM\Column(name: 'currency', type: 'string', nullable: true)]
    protected $currency;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var Collection<QuoteProductKitItemLineItem>
     */
    protected $kitItemLineItems;

    /**
     * Differentiates the unique constraint allowing to add the same product with the same unit code multiple times,
     * moving the logic of distinguishing of such line items out of the entity class.
     */
    #[ORM\Column(name: 'checksum', type: Types::STRING, length: 40, nullable: false, options: ['default' => ''])]
    protected string $checksum = '';

    public function __construct()
    {
        $this->kitItemLineItems = new ArrayCollection();
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
        return $this->getQuoteProduct();
    }

    #[ORM\PostLoad]
    public function postLoad()
    {
        if (null !== $this->value && null !== $this->currency) {
            $this->setPrice(Price::create($this->value, $this->currency));
        }
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatePrice()
    {
        $this->value = $this->price?->getValue();
        $this->currency = $this->price?->getCurrency();
    }

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
     * Set quantity
     *
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set quoteProduct
     *
     * @param QuoteProduct|null $quoteProduct
     * @return $this
     */
    public function setQuoteProduct(QuoteProduct $quoteProduct = null)
    {
        $this->quoteProduct = $quoteProduct;
        $this->loadKitItemLineItems();

        return $this;
    }

    /**
     * Get quoteProduct
     *
     * @return QuoteProduct
     */
    public function getQuoteProduct()
    {
        return $this->quoteProduct;
    }

    /**
     * Set productUnit
     *
     * @param ProductUnit|null $productUnit
     * @return $this
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;
        if ($productUnit) {
            $this->productUnitCode = $productUnit->getCode();
        }

        return $this;
    }

    /**
     * Get productUnit
     *
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * Set productUnitCode
     *
     * @param string $productUnitCode
     * @return $this
     */
    public function setProductUnitCode($productUnitCode)
    {
        $this->productUnitCode = $productUnitCode;

        return $this;
    }

    /**
     * Get productUnitCode
     *
     * @return string
     */
    public function getProductUnitCode()
    {
        return $this->productUnitCode;
    }

    /**
     * Set price
     *
     * @param Price $price
     * @return $this
     */
    public function setPrice($price = null)
    {
        $this->price = $price;

        $this->updatePrice();

        return $this;
    }

    /**
     * Get price
     *
     * @return Price|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /** {@inheritdoc} */
    public function getProduct()
    {
        return $this->quoteProduct?->getProduct();
    }

    /** {@inheritdoc} */
    public function getProductSku()
    {
        return $this->getProduct()?->getSku();
    }

    /**
     * @return null|Product
     */
    public function getParentProduct()
    {
        return $this->quoteProduct?->getParentProduct();
    }

    #[ORM\PostLoad]
    public function loadKitItemLineItems(): void
    {
        if ($this->quoteProduct) {
            $this->kitItemLineItems = $this->quoteProduct->getKitItemLineItems()->map(
                fn (QuoteProductKitItemLineItem $item) => (clone $item)->setLineItem($this)
            );
        } else {
            $this->kitItemLineItems = new ArrayCollection();
        }
    }

    /**
     * @return Collection<QuoteProductKitItemLineItem>
     */
    public function getKitItemLineItems()
    {
        if (!$this->kitItemLineItems->count()) {
            $this->loadKitItemLineItems();
        }

        return $this->kitItemLineItems;
    }

    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }
}
