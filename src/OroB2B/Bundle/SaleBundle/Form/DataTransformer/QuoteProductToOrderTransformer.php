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
        $offerId = null;
        $offerQuantity = null;

        if ($value) {
            if (!$value instanceof QuoteProduct) {
                throw new UnexpectedTypeException($value, 'QuoteProduct');
            }

            $offers = $value->getQuoteProductOffers();
            if ($offers->count() > 0) {
                // first offer is a default value
                /** @var QuoteProductOffer $offer */
                $offer = $offers->first();
                $offerId = $offer->getId();
                $offerQuantity = $offer->getQuantity();
            }
        }

        return [
            QuoteProductToOrderType::FIELD_OFFER => $offerId,
            QuoteProductToOrderType::FIELD_QUANTITY => $offerQuantity,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $offerValue = null;
        $offerQuantity = null;

        if ($value) {
            if (!is_array($value)) {
                throw new UnexpectedTypeException($value, 'array');
            }

            $offerId = $this->getOption($value, QuoteProductToOrderType::FIELD_OFFER);
            $offerQuantity = $this->getOption($value, QuoteProductToOrderType::FIELD_QUANTITY);

            foreach ($this->quoteProduct->getQuoteProductOffers() as $offer) {
                if ($offer->getId() == $offerId) {
                    $offerValue = $offer;
                    break;
                }
            }
        }

        return [
            QuoteProductToOrderType::FIELD_OFFER => $offerValue,
            QuoteProductToOrderType::FIELD_QUANTITY => $offerQuantity,
        ];
    }

    /**
     * @param array $data
     * @param string $option
     * @return mixed
     */
    protected function getOption(array $data, $option)
    {
        return array_key_exists($option, $data) ? $data[$option] : null;
    }
}
