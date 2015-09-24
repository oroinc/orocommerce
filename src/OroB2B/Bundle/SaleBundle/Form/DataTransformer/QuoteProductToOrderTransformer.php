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
        $offerQuantity = null;
        $offerUnit = null;

        if ($value) {
            if (!$value instanceof QuoteProduct) {
                throw new UnexpectedTypeException($value, 'QuoteProduct');
            }

            $offers = $value->getQuoteProductOffers();
            if ($offers->count() > 0) {
                // first offer is a default value
                /** @var QuoteProductOffer $offer */
                $offer = $offers->first();
                $offerQuantity = $offer->getQuantity();
                $offerUnit = $offer->getProductUnitCode();
            }
        }

        return [
            QuoteProductToOrderType::FIELD_QUANTITY => $offerQuantity,
            QuoteProductToOrderType::FIELD_UNIT => $offerUnit,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $offer = null;
        $offerQuantity = null;

        if ($value) {
            if (!is_array($value)) {
                throw new UnexpectedTypeException($value, 'array');
            }

            $offerQuantity = $this->getOption($value, QuoteProductToOrderType::FIELD_QUANTITY);
            $offerUnit = $this->getOption($value, QuoteProductToOrderType::FIELD_UNIT);

            // TODO: use matcher to found offer
            $offer = $this->quoteProduct->getQuoteProductOffers()->last();
        }

        return [
            QuoteProductToOrderType::FIELD_QUANTITY => $offerQuantity,
            QuoteProductToOrderType::FIELD_OFFER => $offer,
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
