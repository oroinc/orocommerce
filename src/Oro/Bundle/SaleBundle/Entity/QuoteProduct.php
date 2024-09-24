<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;

/**
 * Quote Product entity.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_sale_quote_product')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-list-alt'],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'quotes']
    ]
)]
class QuoteProduct implements ProductHolderInterface, ExtendEntityInterface, ProductKitItemLineItemsAwareInterface
{
    use ExtendEntityTrait;

    const TYPE_REQUESTED = 10;
    const TYPE_OFFER = 20;
    const TYPE_NOT_AVAILABLE = 30;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quote::class, inversedBy: 'quoteProducts')]
    #[ORM\JoinColumn(name: 'quote_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Quote $quote = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Product $product = null;

    #[ORM\Column(name: 'free_form_product', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $freeFormProduct = null;

    #[ORM\Column(name: 'product_sku', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $productSku = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_replacement_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Product $productReplacement = null;

    #[ORM\Column(name: 'free_form_product_replacement', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $freeFormProductReplacement = null;

    #[ORM\Column(name: 'product_replacement_sku', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $productReplacementSku = null;

    #[ORM\Column(name: 'type', type: Types::SMALLINT, nullable: true)]
    protected ?int $type = self::TYPE_REQUESTED;

    #[ORM\Column(name: 'comment', type: Types::TEXT, nullable: true)]
    protected ?string $comment = null;

    #[ORM\Column(name: 'comment_customer', type: Types::TEXT, nullable: true)]
    protected ?string $commentCustomer = null;

    /**
     * @var Collection<int, QuoteProductOffer>
     */
    #[ORM\OneToMany(
        mappedBy: 'quoteProduct',
        targetEntity: QuoteProductOffer::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    protected ?Collection $quoteProductOffers = null;

    /**
     * @var Collection<int, QuoteProductRequest>
     */
    #[ORM\OneToMany(
        mappedBy: 'quoteProduct',
        targetEntity: QuoteProductRequest::class,
        cascade: ['ALL'],
        orphanRemoval: true
    )]
    protected ?Collection $quoteProductRequests = null;

    /**
     * @var Collection<QuoteProductKitItemLineItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'quoteProduct',
        targetEntity: QuoteProductKitItemLineItem::class,
        cascade: ['ALL'],
        orphanRemoval: true,
        indexBy: 'kitItemId'
    )]
    #[OrderBy(['sortOrder' => 'ASC'])]
    protected $kitItemLineItems;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->quoteProductOffers = new ArrayCollection();
        $this->quoteProductRequests = new ArrayCollection();
        $this->kitItemLineItems = new ArrayCollection();
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->productSku;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateProducts()
    {
        if ($this->product) {
            $this->productSku = $this->product->getSku();
            $this->freeFormProduct = (string)$this->product;
        }
        if ($this->productReplacement) {
            $this->productReplacementSku = $this->productReplacement->getSku();
            $this->freeFormProductReplacement = (string)$this->productReplacement;
        }
    }

    #[\Override]
    public function getEntityIdentifier()
    {
        return $this->getId();
    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_OFFER => 'offer',
            self::TYPE_REQUESTED => 'requested',
            self::TYPE_NOT_AVAILABLE => 'not_available',
        ];
    }

    /**
     * Check that type is TYPE_OFFER
     *
     * @return boolean
     */
    public function isTypeOffer()
    {
        return static::TYPE_OFFER === $this->getType();
    }

    /**
     * Check that type is TYPE_REQUESTED
     *
     * @return boolean
     */
    public function isTypeRequested()
    {
        return static::TYPE_REQUESTED === $this->getType();
    }

    /**
     * Check that type is TYPE_NOT_AVAILABLE
     *
     * @return boolean
     */
    public function isTypeNotAvailable()
    {
        return static::TYPE_NOT_AVAILABLE === $this->getType();
    }

    /**
     * Check that free form used for product
     *
     * @return boolean
     */
    public function isProductFreeForm()
    {
        return (!$this->product) &&
            (null !== $this->freeFormProduct && '' !== trim($this->freeFormProduct));
    }

    /**
     * Check that free form used for productReplacement
     *
     * @return boolean
     */
    public function isProductReplacementFreeForm()
    {
        return (!$this->productReplacement) &&
            (null !== $this->freeFormProductReplacement && '' !== trim($this->freeFormProductReplacement));
    }

    /**
     * Get actual Product name
     * @return string
     */
    public function getProductName()
    {
        if ($this->isTypeNotAvailable()) {
            return $this->isProductReplacementFreeForm()
                ? $this->freeFormProductReplacement
                : (string)$this->productReplacement;
        }

        return $this->isProductFreeForm()
            ? $this->freeFormProduct
            : (string)$this->product;
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
     * Set quote
     *
     * @param Quote|null $quote
     * @return QuoteProduct
     */
    public function setQuote(Quote $quote = null)
    {
        $this->quote = $quote;

        return $this;
    }

    /**
     * Get quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * Set product
     *
     * @param Product|null $product
     * @return QuoteProduct
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;
        $this->updateProducts();

        return $this;
    }

    /**
     * Get product
     *
     * @return Product
     */
    #[\Override]
    public function getProduct()
    {
        if ($this->isTypeNotAvailable()) {
            return $this->productReplacement;
        } else {
            return $this->product;
        }
    }

    /**
     * @param string $freeFormProduct
     * @return QuoteProduct
     */
    public function setFreeFormProduct($freeFormProduct)
    {
        $this->freeFormProduct = $freeFormProduct;

        return $this;
    }

    /**
     * @return string
     */
    public function getFreeFormProduct()
    {
        return $this->freeFormProduct;
    }

    /**
     * Set productSku
     *
     * @param string $productSku
     * @return QuoteProduct
     */
    public function setProductSku($productSku)
    {
        $this->productSku = $productSku;

        return $this;
    }

    /**
     * Get productSku
     *
     * @return string
     */
    #[\Override]
    public function getProductSku()
    {
        if ($this->isTypeNotAvailable()) {
            return $this->productReplacementSku;
        } else {
            return $this->productSku;
        }
    }

    /**
     * Set productReplacement
     *
     * @param Product|null $productReplacement
     * @return QuoteProduct
     */
    public function setProductReplacement(Product $productReplacement = null)
    {
        $this->productReplacement = $productReplacement;
        $this->updateProducts();

        return $this;
    }

    /**
     * Get productReplacement
     *
     * @return Product
     */
    public function getProductReplacement()
    {
        return $this->productReplacement;
    }

    /**
     * @param string $freeFormProductReplacement
     * @return QuoteProduct
     */
    public function setFreeFormProductReplacement($freeFormProductReplacement)
    {
        $this->freeFormProductReplacement = $freeFormProductReplacement;

        return $this;
    }

    /**
     * @return string
     */
    public function getFreeFormProductReplacement()
    {
        return $this->freeFormProductReplacement;
    }

    /**
     * Set productReplacementSku
     *
     * @param string $productReplacementSku
     * @return QuoteProduct
     */
    public function setProductReplacementSku($productReplacementSku)
    {
        $this->productReplacementSku = $productReplacementSku;

        return $this;
    }

    /**
     * Get productReplacementSku
     *
     * @return string
     */
    public function getProductReplacementSku()
    {
        return $this->productReplacementSku;
    }

    /**
     * Set type
     *
     * @param int $type
     * @return QuoteProduct
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set seller comment
     *
     * @param string $comment
     * @return QuoteProduct
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get seller comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set customer comment
     *
     * @param string $commentCustomer
     * @return QuoteProduct
     */
    public function setCommentCustomer($commentCustomer)
    {
        $this->commentCustomer = $commentCustomer;

        return $this;
    }

    /**
     * Get customer comment
     *
     * @return string
     */
    public function getCommentCustomer()
    {
        return $this->commentCustomer;
    }

    /**
     * Add quoteProductOffer
     *
     * @param QuoteProductOffer $quoteProductOffer
     * @return QuoteProduct
     */
    public function addQuoteProductOffer(QuoteProductOffer $quoteProductOffer)
    {
        if (!$this->quoteProductOffers->contains($quoteProductOffer)) {
            $this->quoteProductOffers->add($quoteProductOffer);
            $quoteProductOffer->setQuoteProduct($this);
        }

        return $this;
    }

    /**
     * Remove quoteProductOffer
     *
     * @param QuoteProductOffer $quoteProductOffer
     * @return QuoteProduct
     */
    public function removeQuoteProductOffer(QuoteProductOffer $quoteProductOffer)
    {
        if ($this->quoteProductOffers->contains($quoteProductOffer)) {
            $this->quoteProductOffers->removeElement($quoteProductOffer);
        }

        return $this;
    }

    /**
     * Get quoteProductOffers
     *
     * @return Collection|QuoteProductOffer[]
     */
    public function getQuoteProductOffers()
    {
        return $this->quoteProductOffers;
    }

    /**
     * @param int $priceType
     * @return bool
     */
    public function hasQuoteProductOfferByPriceType($priceType)
    {
        foreach ($this->quoteProductOffers as $offer) {
            if ($offer->getPriceType() == $priceType) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add quoteProductRequest
     *
     * @param QuoteProductRequest $quoteProductRequest
     * @return QuoteProduct
     */
    public function addQuoteProductRequest(QuoteProductRequest $quoteProductRequest)
    {
        if (!$this->quoteProductRequests->contains($quoteProductRequest)) {
            $this->quoteProductRequests->add($quoteProductRequest);
            $quoteProductRequest->setQuoteProduct($this);
        }

        return $this;
    }

    /**
     * Remove quoteProductRequest
     *
     * @param QuoteProductRequest $quoteProductRequest
     * @return QuoteProduct
     */
    public function removeQuoteProductRequest(QuoteProductRequest $quoteProductRequest)
    {
        if ($this->quoteProductRequests->contains($quoteProductRequest)) {
            $this->quoteProductRequests->removeElement($quoteProductRequest);
        }

        return $this;
    }

    /**
     * Get quoteProductRequests
     *
     * @return Collection|QuoteProductRequest[]
     */
    public function getQuoteProductRequests()
    {
        return $this->quoteProductRequests;
    }

    /**
     * @return bool
     */
    public function hasOfferVariants()
    {
        if (count($this->quoteProductOffers) > 1) {
            return true;
        }

        /** @var QuoteProductOffer $firstItem */
        $firstItem = $this->quoteProductOffers->first();

        return $firstItem && $firstItem->isAllowIncrements();
    }

    /**
     * @return bool
     */
    public function hasIncrementalOffers()
    {
        foreach ($this->quoteProductOffers as $offer) {
            if ($offer->isAllowIncrements()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return null|Product
     */
    public function getParentProduct()
    {
        // QuoteProduct doesn't support configurable products
        return null;
    }

    /**
     * @return Collection<QuoteProductKitItemLineItem>
     */
    #[\Override]
    public function getKitItemLineItems()
    {
        return $this->kitItemLineItems;
    }

    public function addKitItemLineItem(QuoteProductKitItemLineItem $productKitItemLineItem): self
    {
        $index = $productKitItemLineItem->getKitItemId();

        if (!$this->kitItemLineItems->containsKey($index)) {
            $productKitItemLineItem->setQuoteProduct($this);
            if ($index) {
                $this->kitItemLineItems->set($index, $productKitItemLineItem);
            } else {
                $this->kitItemLineItems->add($productKitItemLineItem);
            }
        }

        return $this;
    }

    public function removeKitItemLineItem(QuoteProductKitItemLineItem $productKitItemLineItem): self
    {
        $this->kitItemLineItems->removeElement($productKitItemLineItem);

        return $this;
    }
}
