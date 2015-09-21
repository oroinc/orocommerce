<?php

namespace OroB2B\Bundle\SaleBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductToOrderType;

class QuoteProductToOrderTransformer implements DataTransformerInterface
{
    /**
     * @var QuoteProduct
     */
    protected $quoteProduct;

    /**
     * @param QuoteProduct $quoteProduct
     */
    public function __construct(QuoteProduct $quoteProduct)
    {
        $this->quoteProduct = $quoteProduct;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            return [];
        }

        if (!$value instanceof QuoteProduct) {
            throw new UnexpectedTypeException($value, 'QuoteProduct');
        }

        /** @var QuoteProductOffer $offer */
        $offer = $value->getQuoteProductOffers()->first();

        return [
            QuoteProductToOrderType::FIELD_OFFER => $offer->getId(),
            QuoteProductToOrderType::FIELD_QUANTITY => $offer->getQuantity(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        $offerId = $value[QuoteProductToOrderType::FIELD_OFFER];
        $offerValue = null;
        foreach ($this->quoteProduct->getQuoteProductOffers() as $offer) {
            if ($offer->getId() == $offerId) {
                $offerValue = $offer;
                break;
            }
        }

        $value[QuoteProductToOrderType::FIELD_OFFER] = $offerValue;

        return $value;
    }
}
