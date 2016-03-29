<?php

namespace OroB2B\Bundle\SaleBundle\Provider;

use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Model\QuoteOfferConverter;
use OroB2B\Component\Checkout\DataProvider\AbstractCheckoutProvider;

class QuoteCheckoutLineItemDataProvider extends AbstractCheckoutProvider
{
    /**
     * {@inheritDoc}
     */
    public function isEntitySupported($entity)
    {
        return $entity instanceof QuoteDemand;
    }

    /**
     * @param QuoteDemand $entity
     * @return array
     */
    protected function prepareData($entity)
    {
        $result = [];
        foreach ($entity->getDemandOffers() as $offer) {
            /** @var QuoteProductOffer $productOffer */
            $productOffer = $offer->getQuoteProductOffer();
            $result[] = [
                'product' => $productOffer->getProduct(),
                'productSku' => $productOffer->getProductSku(),
                'quantity' => $productOffer->getQuantity(),
                'productUnit' => $productOffer->getProductUnit(),
                'productUnitCode' => $productOffer->getProductUnitCode(),
                'price' => $productOffer->getPrice(),
                'fromExternalSource' => true,
            ];
        }

        return $result;
    }
}
