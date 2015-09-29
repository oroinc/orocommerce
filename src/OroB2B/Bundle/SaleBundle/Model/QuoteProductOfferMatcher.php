<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteProductOfferMatcher
{
    /**
     * @param QuoteProduct $quoteProduct
     * @param string $unitCode
     * @param float $quantity
     * @return QuoteProductOffer|null
     */
    public function match(QuoteProduct $quoteProduct, $unitCode, $quantity)
    {
        $expectedQuantity = (float)$quantity;

        $offers = $quoteProduct->getQuoteProductOffers()->filter(
            function (QuoteProductOffer $offer) use ($unitCode) {
                return $offer->getProductUnitCode() === $unitCode;
            }
        );

        $offers = $this->sortOfferByQuantity($offers->toArray());

        $result = null;
        /** @var QuoteProductOffer[] $offers */
        foreach ($offers as $offer) {
            $quantity = (float)$offer->getQuantity();
            $allowIncrements = $offer->isAllowIncrements();

            if ($quantity > $expectedQuantity) {
                break;
            }

            if ($allowIncrements) {
                $result = $offer;
            } elseif ($quantity === $expectedQuantity) {
                $result = $offer;
                break;
            }
        }

        return $result;
    }

    /**
     * @param array $offers
     * @return array
     */
    protected function sortOfferByQuantity(array $offers)
    {
        usort(
            $offers,
            function (QuoteProductOffer $offer1, QuoteProductOffer $offer2) {
                $quantity1 = (float)$offer1->getQuantity();
                $quantity2 = (float)$offer2->getQuantity();

                if ($quantity1 === $quantity2) {
                    return 0;
                }

                return $quantity1 > $quantity2 ? 1 : -1;
            }
        );

        return $offers;
    }
}
