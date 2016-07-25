<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\OrderBundle\Model\ShippingAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use OroB2B\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 *
 * @ORM\Table(name="orob2b_quote_demand")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt",
 *              "totals_mapping"={
 *                  "type"="entity_fields",
 *                  "fields"={
 *                       "currency"="totalCurrency",
 *                       "subtotal"="subtotal",
 *                       "total"="total"
 *                  }
 *              }
 *          }
 *      }
 * )
 */
class QuoteDemand implements
    CheckoutSourceEntityInterface,
    LineItemsAwareInterface,
    ShippingAwareInterface,
    SubtotalAwareInterface
{
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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\SaleBundle\Entity\Quote")
     * @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quote;

    /**
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand",
     *     mappedBy="quoteDemand", cascade={"all"}, orphanRemoval=true)
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $demandProducts;

    /**
     * @var float
     *
     * @ORM\Column(name="subtotal", type="money", nullable=true)
     */
    protected $subtotal;

    /**
     * @var float
     *
     * @ORM\Column(name="total", type="money", nullable=true)
     */
    protected $total;

    /**
     * @var string
     *
     * @ORM\Column(name="total_currency", type="string", length=3, nullable=true)
     */
    protected $totalCurrency;

    public function __construct()
    {
        $this->demandProducts = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @param Quote $quote
     * @return $this
     */
    public function setQuote(Quote $quote)
    {
        $this->quote = $quote;
        $this->initQuoteProductDemands();

        return $this;
    }

    /**
     * @return QuoteProductDemand[]|Collection
     */
    public function getDemandProducts()
    {
        return $this->demandProducts;
    }

    /**
     * @param QuoteProductDemand $demandProduct
     * @return $this
     */
    public function addDemandProduct(QuoteProductDemand $demandProduct)
    {
        if (!$this->hasDemandProduct($demandProduct)) {
            $this->demandProducts->add($demandProduct);
        }

        return $this;
    }

    /**
     * @param QuoteProductDemand $demandProduct
     * @return $this
     */
    public function removeDemandProduct(QuoteProductDemand $demandProduct)
    {
        if ($this->hasDemandProduct($demandProduct)) {
            $this->demandProducts->removeElement($demandProduct);
        }

        return $this;
    }

    /**
     * @param QuoteProductDemand $demandProduct
     * @return bool
     */
    protected function hasDemandProduct(QuoteProductDemand $demandProduct)
    {
        return $this->demandProducts->contains($demandProduct);
    }

    /**
     * @return ArrayCollection|QuoteProductDemand[]
     */
    public function getLineItems()
    {
        return $this->demandProducts;
    }

    /**
     * @return Price|null
     */
    public function getShippingCost()
    {
        if ($this->quote) {
            return $this->quote->getShippingEstimate();
        }

        return null;
    }

    protected function initQuoteProductDemands()
    {
        foreach ($this->quote->getQuoteProducts() as $quoteProduct) {
            $offer = $quoteProduct->getQuoteProductOffers()->first();
            $demandProduct = new QuoteProductDemand($this, $offer, $offer->getQuantity());
            $this->addDemandProduct($demandProduct);
        }
    }

    /**
     * @return Quote
     */
    public function getSourceDocument()
    {
        return $this->quote;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceDocumentIdentifier()
    {
        return $this->quote->getPoNumber();
    }

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return $this
     */
    public function setTotalCurrency($currency)
    {
        $this->totalCurrency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getTotalCurrency()
    {
        return $this->totalCurrency;
    }

    /**
     * Set subtotal
     *
     * @param float $subtotal
     *
     * @return $this
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    /**
     * Set total
     *
     * @param float $total
     *
     * @return $this
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }
}
