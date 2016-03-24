<?php

namespace OroB2B\Bundle\SaleBundle\Provider;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Model\QuoteOfferConverter;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface;

class QuoteCheckoutLineItemDataProvider implements CheckoutDataProviderInterface
{
    /** @var  QuoteOfferConverter */
    protected $quoteOfferConverter;

    /**
     * @param QuoteOfferConverter $quoteOfferConverter
     */
    public function __construct(QuoteOfferConverter $quoteOfferConverter)
    {
        $this->quoteOfferConverter = $quoteOfferConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($entity, $additionalData)
    {
        $data = $this->quoteOfferConverter->toModel($additionalData);
        $result = [];
        foreach ($data as $offer) {
            /** @var QuoteProductOffer $productOffer */
            $productOffer = $offer[QuoteOfferConverter::OFFER];
            $result[] = [
                'product' => $productOffer->getProduct(),
                'productSku' => $productOffer->getProductSku(),
                'quantity' => $productOffer->getQuantity(),
                'productUnit' => $productOffer->getProductUnit(),
                'freeFromProduct' => $productOffer->getQuoteProduct()->getFreeFormProduct(),
                'productUnitCode' => $productOffer->getProductUnitCode(),
                'price' => $productOffer->getPrice()
            ];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function isEntitySupported($entity)
    {
        return $entity instanceof Quote;
    }
}
