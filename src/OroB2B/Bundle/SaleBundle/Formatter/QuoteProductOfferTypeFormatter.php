<?php

namespace OroB2B\Bundle\SaleBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

class QuoteProductOfferTypeFormatter
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string $priceType
     * @return string
     */
    public function formatPriceTypeLabel($priceType)
    {
        $translationKey = sprintf('orob2b.sale.quoteproductoffer.price_type.%s', $priceType);

        return $this->translator->trans($translationKey);
    }

    /**
     * @param array $types
     * @return array
     */
    public function formatPriceTypeLabels(array $types)
    {
        $res = [];

        foreach ($types as $key => $value) {
            $res[$key] = $this->formatPriceTypeLabel($value);
        }

        return $res;
    }
}
