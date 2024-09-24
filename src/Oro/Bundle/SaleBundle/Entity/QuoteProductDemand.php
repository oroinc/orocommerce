<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Entity that represents quote product demand
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_quote_product_demand')]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-list-alt']])]
class QuoteProductDemand implements
    PriceAwareInterface,
    PriceTypeAwareInterface,
    ProductLineItemInterface,
    ExtendEntityInterface,
    ProductKitItemLineItemsAwareInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: QuoteDemand::class, inversedBy: 'demandProducts')]
    #[ORM\JoinColumn(name: 'quote_demand_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?QuoteDemand $quoteDemand = null;

    #[ORM\ManyToOne(targetEntity: QuoteProductOffer::class)]
    #[ORM\JoinColumn(name: 'quote_product_offer_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?QuoteProductOffer $quoteProductOffer = null;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'quantity', type: Types::FLOAT)]
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
     */
    #[ORM\Column(name: 'checksum', type: Types::STRING, length: 40, nullable: false, options: ['default' => ''])]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    public function getPriceType()
    {
        return $this->getQuoteProductOffer()->getPriceType();
    }

    #[\Override]
    public function getProduct()
    {
        return $this->getQuoteProductOffer()->getProduct();
    }

    #[\Override]
    public function getProductSku()
    {
        return $this->getQuoteProductOffer()->getProductSku();
    }

    #[\Override]
    public function getProductUnit()
    {
        return $this->getQuoteProductOffer()->getProductUnit();
    }

    #[\Override]
    public function getProductUnitCode()
    {
        return $this->getQuoteProductOffer()->getProductUnitCode();
    }

    #[\Override]
    public function getParentProduct()
    {
        return $this->getQuoteProductOffer()->getParentProduct();
    }

    /**
     * @return Collection<QuoteProductKitItemLineItem>
     */
    #[\Override]
    public function getKitItemLineItems()
    {
        if (!$this->kitItemLineItems) {
            $this->loadKitItemLineItems();
        }

        return $this->kitItemLineItems;
    }

    #[ORM\PostLoad]
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
