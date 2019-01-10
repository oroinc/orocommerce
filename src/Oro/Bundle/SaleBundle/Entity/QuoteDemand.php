<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerUserAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\PreConfiguredShippingMethodConfigurationInterface;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Entity that represents quote demand
 *
 * @ORM\Table(name="oro_quote_demand")
 * @ORM\Entity(repositoryClass="Oro\Bundle\SaleBundle\Entity\Repository\QuoteDemandRepository")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-list-alt",
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
    SubtotalAwareInterface,
    CustomerOwnerAwareInterface,
    CustomerVisitorOwnerAwareInterface,
    ProductLineItemsHolderInterface,
    PreConfiguredShippingMethodConfigurationInterface
{
    use AuditableFrontendCustomerUserAwareTrait;

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
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\SaleBundle\Entity\Quote", inversedBy="demands")
     * @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quote;

    /**
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\SaleBundle\Entity\QuoteProductDemand",
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

    /**
     * @var CustomerVisitor|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerVisitor")
     * @ORM\JoinColumn(name="visitor_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $visitor;

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
     * {@inheritDoc}
     */
    public function getShippingCost()
    {
        return $this->quote ? $this->quote->getShippingCost() : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethod()
    {
        return $this->quote ? $this->quote->getShippingMethod() : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethodType()
    {
        return $this->quote ? $this->quote->getShippingMethodType() : null;
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

    /**
     * {@inheritdoc}
     */
    public function getVisitor()
    {
        return $this->visitor;
    }

    /**
     * @param CustomerVisitor $visitor
     *
     * @return $this
     */
    public function setVisitor(CustomerVisitor $visitor)
    {
        $this->visitor = $visitor;

        return $this;
    }
}
