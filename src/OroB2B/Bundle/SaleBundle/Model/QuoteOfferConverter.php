<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteOfferConverter
{
    const OFFER_ID = 'offer_id';
    const QUANTITY = 'quantity';

    /**
     * @param array $offers
     * @return array
     */
    public function toArray(array $offers)
    {
        $result = [];
        foreach ($offers as $offer) {
            /** @var QuoteProductOffer $offerEntity */
            $offerEntity = $offer['offer'];
            $res[self::OFFER_ID] = $offerEntity->getId();
            $res[self::QUANTITY] = $offer[self::QUANTITY];
            $result[] = $res;
        }

        return $result;
    }
}
