<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use OroB2B\Bundle\SaleBundle\Entity\Quote;

class QuoteOfferDTO
{
    /** @var  Quote */
    protected $quote;

    /**
     * @var null|array
     */
    protected $offersData;

    /**
     * QuoteOfferDTO constructor.
     * @param Quote $quote
     * @param array|null $offersData
     */
    public function __construct(Quote $quote, $offersData = null)
    {
        $this->quote = $quote;
        $this->offersData = $offersData;
    }
}
