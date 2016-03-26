<?php

namespace OroB2B\Bundle\SaleBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductSelectedOffer;

class QuoteProductSelectedOfferRepository extends EntityRepository
{
    /**
     * @param Quote $quote
     * @return QuoteProductSelectedOffer[]
     */
    public function getSavedOffersByQuote(Quote $quote)
    {
        /** @var QuoteProductSelectedOffer[] $offers */
        $offers = $this->findBy(['quote' => $quote]);
        $selectedOffers = [];
        foreach ($offers as $offer) {
            $selectedOffers[$offer->getQuoteProductOffer()->getId()] = $offer;
        }

        return $selectedOffers;
    }
}
