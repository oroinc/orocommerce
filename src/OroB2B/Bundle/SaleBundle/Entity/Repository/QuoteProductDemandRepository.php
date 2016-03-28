<?php

namespace OroB2B\Bundle\SaleBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand;

class QuoteProductDemandRepository extends EntityRepository
{
    /**
     * @param QuoteDemand $quoteDemand
     * @return QuoteProductDemand[]
     */
    public function getSavedOffersByQuote(QuoteDemand $quoteDemand)
    {
        /** @var QuoteProductDemand[] $offers */
        $offers = $this->findBy(['quoteDemand' => $quoteDemand]);
        $selectedOffers = [];
        foreach ($offers as $offer) {
            $selectedOffers[$offer->getQuoteProductOffer()->getId()] = $offer;
        }

        return $selectedOffers;
    }
}
