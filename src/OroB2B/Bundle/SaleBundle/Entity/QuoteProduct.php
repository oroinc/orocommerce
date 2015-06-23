<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Table(name="orob2b_sale_quote_product")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class QuoteProduct
{
    const TYPE_REQUESTED = 10;
    const TYPE_OFFER = 20;
    const TYPE_NOT_AVAILABLE = 30;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Quote
     *
     * @ORM\ManyToOne(targetEntity="Quote", inversedBy="quoteProducts")
     * @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quote;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $product;

    /**
     * @var string
     *
     * @ORM\Column(name="product_sku", type="string", length=255)
     */
    protected $productSku;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", nullable=true)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="comment_customer", type="text", nullable=true)
     */
    protected $commentCustomer;

    /**
     * @var Collection|QuoteProductOffer[]
     *
     * @ORM\OneToMany(targetEntity="QuoteProductOffer", mappedBy="quoteProduct", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $quoteProductOffers;

    /**
     * @var Collection|QuoteProductRequest[]
     *
     * @ORM\OneToMany(targetEntity="QuoteProductRequest", mappedBy="quoteProduct", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $quoteProductRequests;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->quoteProductOffers = new ArrayCollection();
        $this->quoteProductRequests = new ArrayCollection();
    }

    /**
     * Get Type Titles array
     *
     * @return array
     */
    public static function getTypeTitles()
    {
        static $typeTitles = null;
        if (null === $typeTitles) {
            $typeTitles = [
                static::TYPE_REQUESTED => 'orob2b.sale.quoteproduct.type.requested',
                static::TYPE_OFFER => 'orob2b.sale.quoteproduct.type.offer',
                static::TYPE_NOT_AVAILABLE => 'orob2b.sale.quoteproduct.type.not_available',
            ];
        }

        return $typeTitles;
    }

    /**
     * Get Type Title
     *
     * @return string
     */
    public function getTypeTitle()
    {
        $status = $this->getType();
        $titles = static::getTypeTitles();

        return isset($titles[$status]) ? $titles[$status] : '';
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
     * @param Quote $quote
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
     * @param Product $product
     * @return QuoteProduct
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;
        if ($product) {
            $this->productSku = $product->getSku();
        }

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
    public function getProductSku()
    {
        return $this->productSku;
    }

    /**
     * @param int $type
     * @return QuoteProduct
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $comment
     * @return QuoteProduct
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $commentCustomer
     * @return QuoteProduct
     */
    public function setCommentCustomer($commentCustomer)
    {
        $this->commentCustomer = $commentCustomer;

        return $this;
    }

    /**
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
            $this->quoteProductOffers[] = $quoteProductOffer;
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
     * Add quoteProductRequest
     *
     * @param QuoteProductRequest $quoteProductRequest
     * @return QuoteProduct
     */
    public function addQuoteProductRequest(QuoteProductRequest $quoteProductRequest)
    {
        if (!$this->quoteProductRequests->contains($quoteProductRequest)) {
            $this->quoteProductRequests[] = $quoteProductRequest;
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
}
