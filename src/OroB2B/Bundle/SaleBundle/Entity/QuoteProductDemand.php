<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use OroB2B\Bundle\ProductBundle\Model\QuantityAwareInterface;
use OroB2B\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;

/**
 * SelectedOffers
 *
 * @ORM\Table(name="orob2b_quote_product_demand")
 * @ORM\Entity
 */
class QuoteProductDemand implements PriceAwareInterface, QuantityAwareInterface, PriceTypeAwareInterface
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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\SaleBundle\Entity\QuoteDemand", inversedBy="demandProducts")
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
     * @return Quote
     */
    public function getQuoteDemand()
    {
        return $this->quoteDemand;
    }

    /**
     * @param Quote $quoteDemand
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
     * @param Price $price
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
}
