<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\Ownership\AuditableFrontendCustomerUserAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteDemandRepository;
use Oro\Bundle\ShippingBundle\Method\Configuration\PreConfiguredShippingMethodConfigurationInterface;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Entity that represents quote demand
 */
#[ORM\Entity(repositoryClass: QuoteDemandRepository::class)]
#[ORM\Table(name: 'oro_quote_demand')]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-list-alt']])]
class QuoteDemand implements
    CheckoutSourceEntityInterface,
    LineItemsAwareInterface,
    ShippingAwareInterface,
    SubtotalAwareInterface,
    CustomerOwnerAwareInterface,
    CustomerVisitorOwnerAwareInterface,
    ProductLineItemsHolderInterface,
    PreConfiguredShippingMethodConfigurationInterface,
    ExtendEntityInterface
{
    use AuditableFrontendCustomerUserAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quote::class, inversedBy: 'demands')]
    #[ORM\JoinColumn(name: 'quote_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Quote $quote = null;

    /**
     * @var Collection<int, QuoteProductDemand>
     */
    #[ORM\OneToMany(
        mappedBy: 'quoteDemand',
        targetEntity: QuoteProductDemand::class,
        cascade: ['all'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    protected ?Collection $demandProducts = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'subtotal', type: 'money', nullable: true)]
    protected $subtotal;

    /**
     * @var float
     */
    #[ORM\Column(name: 'total', type: 'money', nullable: true)]
    protected $total;

    #[ORM\Column(name: 'total_currency', type: Types::STRING, length: 3, nullable: true)]
    protected ?string $totalCurrency = null;

    #[ORM\ManyToOne(targetEntity: CustomerVisitor::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'visitor_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?CustomerVisitor $visitor = null;

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
    #[\Override]
    public function getLineItems()
    {
        return $this->demandProducts;
    }

    #[\Override]
    public function getShippingCost()
    {
        return $this->quote ? $this->quote->getShippingCost() : null;
    }

    #[\Override]
    public function getShippingMethod()
    {
        return $this->quote ? $this->quote->getShippingMethod() : null;
    }

    #[\Override]
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
    #[\Override]
    public function getSourceDocument()
    {
        return $this->quote;
    }

    #[\Override]
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

    #[\Override]
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

    #[\Override]
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
