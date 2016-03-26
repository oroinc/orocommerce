<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * SelectedOffers
 *
 * @ORM\Table(name="orob2b_sale_selected_offer")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\SaleBundle\Entity\Repository\QuoteProductSelectedOfferRepository")
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
class QuoteProductSelectedOffer
{
    /**
     * @var Quote
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Quote")
     * @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quote;

    /**
     * @var QuoteProductOffer
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="QuoteProductOffer")
     * @ORM\JoinColumn(name="quote_product_offer", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quoteProductOffer;

    /**
     * @var integer
     * @ORM\Column(name="quantity", type="integer")
     */
    protected $quantity;

    /**
     * SelectedOffer constructor.
     * @param Quote $quote
     * @param QuoteProductOffer $quoteProductOffer
     * @param int $quantity
     */
    public function __construct(Quote $quote, QuoteProductOffer $quoteProductOffer, $quantity)
    {
        $this->quote = $quote;
        $this->quoteProductOffer = $quoteProductOffer;
        $this->quantity = $quantity;
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
     */
    public function setQuote($quote)
    {
        $this->quote = $quote;
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
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }
}
