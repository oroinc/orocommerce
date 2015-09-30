<?php

namespace OroB2B\Bundle\SaleBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductToOrderType;
use OroB2B\Bundle\SaleBundle\Model\QuoteProductOfferMatcher;

class QuoteProductToOrderTransformer implements DataTransformerInterface
{
    /**
     * @var QuoteProductOfferMatcher
     */
    protected $matcher;

    /**
     * @var RoundingService
     */
    protected $roundingService;

    /**
     * @var QuoteProduct
     */
    protected $quoteProduct;

    /**
     * @param QuoteProductOfferMatcher $matcher
     * @param RoundingService $roundingService
     * @param QuoteProduct $quoteProduct
     */
    public function __construct(
        QuoteProductOfferMatcher $matcher,
        RoundingService $roundingService,
        QuoteProduct $quoteProduct
    ) {
        $this->matcher = $matcher;
        $this->roundingService = $roundingService;
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
                $offerQuantity = $this->roundQuantity($offerQuantity, $offerUnit);
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

            if ($offerQuantity && $offerUnit) {
                $offerQuantity = $this->roundQuantity($offerQuantity, $offerUnit);
                $offer = $this->matcher->match($this->quoteProduct, $offerUnit, $offerQuantity);
            }
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

    /**
     * @param float $quantity
     * @param string $unitCode
     * @return float
     */
    protected function roundQuantity($quantity, $unitCode)
    {
        $precision = 0;
        $product = $this->quoteProduct->getProductReplacement() ?: $this->quoteProduct->getProduct();
        if ($product) {
            $unitPrecision = $product->getUnitPrecision($unitCode);
            if ($unitPrecision) {
                $precision = $unitPrecision->getPrecision();
            }
        }

        return $this->roundingService->round($quantity, $precision);
    }
}
