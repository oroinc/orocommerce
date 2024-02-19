<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Entity that represents quote product demand
 *
 * @ORM\Table(name="oro_quote_product_demand")
 * @ORM\Entity
 * @Config(
 *       defaultValues={
 *           "entity"={
 *               "icon"="fa-list-alt"
 *           }
 *       }
 *  )
 * @ORM\HasLifecycleCallbacks()
 */
class QuoteProductDemand implements
    PriceAwareInterface,
    PriceTypeAwareInterface,
    ProductLineItemInterface,
    ExtendEntityInterface,
    ProductKitItemLineItemsAwareInterface
{
    use ExtendEntityTrait;

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
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\SaleBundle\Entity\QuoteDemand", inversedBy="demandProducts")
     * @ORM\JoinColumn(name="quote_demand_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quoteDemand;

    /**
     * @var QuoteProductOffer
     * @ORM\ManyToOne(targetEntity="QuoteProductOffer")
     * @ORM\JoinColumn(name="quote_product_offer_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quoteProductOffer;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float")
     */
    protected $quantity;

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
     *
     * @ORM\Column(name="checksum", type="string", length=40, options={"default"=""}, nullable=false)
     */
    protected string $checksum = '';

    /**
     * SelectedOffer constructor.
     * @param QuoteDemand $quoteDemand
     * @param QuoteProductOffer $quoteProductOffer
     * @param float $quantity
     */
    public function __construct(QuoteDemand $quoteDemand, QuoteProductOffer $quoteProductOffer, $quantity)
    {
        $this->quoteDemand = $quoteDemand;
        $this->quoteProductOffer = $quoteProductOffer;
        $this->quantity = $quantity;
        $this->kitItemLineItems = new ArrayCollection();
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
        return $this;
    }

    /**
     * @return QuoteProductOffer
     */
    public function getQuoteProductOffer()
    {
        return $this->quoteProductOffer;
    }

    /**
     * @param QuoteProductOffer $quoteProductOffer
     */
    public function setQuoteProductOffer($quoteProductOffer)
    {
        $this->quoteProductOffer = $quoteProductOffer;
        $this->loadKitItemLineItems();
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return QuoteDemand
     */
    public function getQuoteDemand()
    {
        return $this->quoteDemand;
    }

    /**
     * @param QuoteDemand $quoteDemand
     */
    public function setQuoteDemand($quoteDemand)
    {
        $this->quoteDemand = $quoteDemand;
    }

    /**
     * @return Price
     */
    public function getPrice()
    {
        return $this->getQuoteProductOffer()->getPrice();
    }

    /**
     * @param Price|null $price
     * @return $this
     * @throws \LogicException
     */
    public function setPrice(Price $price = null)
    {
        throw new \LogicException('Price can\'t be changed to this entity');
    }

    /**
     * @return int
     */
    public function getPriceType()
    {
        return $this->getQuoteProductOffer()->getPriceType();
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct()
    {
        return $this->getQuoteProductOffer()->getProduct();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductSku()
    {
        return $this->getQuoteProductOffer()->getProductSku();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnit()
    {
        return $this->getQuoteProductOffer()->getProductUnit();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnitCode()
    {
        return $this->getQuoteProductOffer()->getProductUnitCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentProduct()
    {
        return $this->getQuoteProductOffer()->getParentProduct();
    }

    /**
     * @return Collection<QuoteProductKitItemLineItem>
     */
    public function getKitItemLineItems()
    {
        return $this->kitItemLineItems;
    }

    /**
     * @ORM\PostLoad
     */
    public function loadKitItemLineItems(): void
    {
        if ($this->quoteProductOffer) {
            $this->kitItemLineItems = $this->quoteProductOffer->getKitItemLineItems()->map(
                fn (QuoteProductKitItemLineItem $item) => (clone $item)->setLineItem($this->quoteProductOffer)
            );
        } else {
            $this->kitItemLineItems = new ArrayCollection();
        }
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
