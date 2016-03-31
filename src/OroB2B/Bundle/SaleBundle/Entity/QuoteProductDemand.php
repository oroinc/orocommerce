<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * SelectedOffers
 *
 * @ORM\Table(name="orob2b_quote_product_demand")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          }
 *      }
 * )
 */
class QuoteProductDemand
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
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\SaleBundle\Entity\QuoteDemand")
     * @ORM\JoinColumn(name="quote_demand_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quoteDemand;
    
    /**
     * @var QuoteProductOffer
     * @ORM\ManyToOne(targetEntity="QuoteProductOffer")
     * @ORM\JoinColumn(name="quote_product_offer", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quoteProductOffer;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float")
     */
    protected $quantity;

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
}
