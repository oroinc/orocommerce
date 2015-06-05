<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Table(name="orob2b_sale_quote_product")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
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
    /**
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
     * @var Collection|QuoteProductItem[]
     *
     * @ORM\OneToMany(targetEntity="QuoteProductItem", mappedBy="quoteProduct", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $quoteProductItems;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->quoteProductItems = new ArrayCollection();
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
     * Add quoteProductItem
     *
     * @param QuoteProductItem $quoteProductItem
     * @return QuoteProduct
     */
    public function addQuoteProductItem(QuoteProductItem $quoteProductItem)
    {
        if (!$this->quoteProductItems->contains($quoteProductItem)) {
            $this->quoteProductItems[] = $quoteProductItem;
            $quoteProductItem->setQuoteProduct($this);
        }

        return $this;
    }

    /**
     * Remove quoteProductItem
     *
     * @param QuoteProductItem $quoteProductItem
     */
    public function removeQuoteProductItem(QuoteProductItem $quoteProductItem)
    {
        $this->quoteProductItems->removeElement($quoteProductItem);
    }

    /**
     * Get quoteProductItems
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQuoteProductItems()
    {
        return $this->quoteProductItems;
    }
}
