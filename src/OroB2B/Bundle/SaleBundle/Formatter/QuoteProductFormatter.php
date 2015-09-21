<?php

namespace OroB2B\Bundle\SaleBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;
use OroB2B\Bundle\SaleBundle\Model\BaseQuoteProductItem;

class QuoteProductFormatter
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ProductUnitValueFormatter
     */
    protected $productUnitValueFormatter;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $productUnitLabelFormatter;

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @param TranslatorInterface $translator
     * @param NumberFormatter $numberFormatter
     * @param ProductUnitValueFormatter $productUnitValueFormatter
     * @param ProductUnitLabelFormatter $productUnitLabelFormatter
     */
    public function __construct(
        TranslatorInterface $translator,
        NumberFormatter $numberFormatter,
        ProductUnitValueFormatter $productUnitValueFormatter,
        ProductUnitLabelFormatter $productUnitLabelFormatter
    ) {
        $this->translator                   = $translator;
        $this->numberFormatter              = $numberFormatter;
        $this->productUnitValueFormatter    = $productUnitValueFormatter;
        $this->productUnitLabelFormatter    = $productUnitLabelFormatter;
    }

    /**
     * @param string $type
     * @return string
     */
    public function formatType($type)
    {
        $types = QuoteProduct::getTypes();

        if (isset($types[$type])) {
            return $this->formatTypeLabel($types[$type]);
        } else {
            return $this->translator->trans('N/A');
        }
    }

    /**
     * @param string $typeLabel
     * @return string
     */
    public function formatTypeLabel($typeLabel)
    {
        $translationKey = sprintf('orob2b.sale.quoteproduct.type.%s', $typeLabel);

        return $this->translator->trans($translationKey);
    }

    /**
     * @param array $types
     * @return array
     */
    public function formatTypeLabels(array $types)
    {
        $res = [];

        foreach ($types as $key => $value) {
            $res[$key] = $this->formatTypeLabel($value);
        }

        return $res;
    }

    /**
     * @param QuoteProductRequest $item
     * @return string
     */
    public function formatRequest(QuoteProductRequest $item)
    {
        $default = $this->translator->trans('N/A');

        if (!$item->getQuantity() && !$item->getPrice()) {
            return $default;
        }

        return $this->translator->trans(
            'orob2b.sale.quoteproductrequest.item',
            [
                '{units}'   => $this->formatProductUnit($item, $default),
                '{price}'   => $this->formatPrice($item, $default),
                '{unit}'    => $this->formatUnitCode($item),
            ]
        );
    }

    /**
     * @param QuoteProductOffer $item
     * @return string
     */
    public function formatOffer(QuoteProductOffer $item)
    {
        switch ($item->getPriceType()) {
            case QuoteProductOffer::PRICE_TYPE_BUNDLED:
                $transConstant = 'orob2b.sale.quoteproductoffer.item_bundled';
                break;
            default:
                $transConstant = 'orob2b.sale.quoteproductoffer.item';
        }

        $str = $this->translator->transChoice(
            $transConstant,
            (int)$item->isAllowIncrements(),
            [
                '{units}'   => $this->formatProductUnit($item),
                '{price}'   => $this->formatPrice($item),
                '{unit}'    => $this->formatUnitCode($item),
            ]
        );

        return $str;
    }

    /**
     * @param BaseQuoteProductItem $item
     * @param string $default
     * @return string
     */
    protected function formatProductUnit(BaseQuoteProductItem $item, $default = '')
    {
        if (!$item->getProductUnit()) {
            return sprintf('%s %s', $item->getQuantity(), $item->getProductUnitCode());
        } elseif ($item->getQuantity()) {
            return $this->productUnitValueFormatter->format($item->getQuantity(), $item->getProductUnit());
        }

        return $default;
    }

    /**
     * @param BaseQuoteProductItem $item
     * @param string $default
     * @return string
     */
    protected function formatPrice(BaseQuoteProductItem $item, $default = '')
    {
        if ($item->getPrice()) {
            return $this->numberFormatter->formatCurrency(
                $item->getPrice()->getValue(),
                $item->getPrice()->getCurrency()
            );
        }

        return $default;
    }

    /**
     * @param BaseQuoteProductItem $item
     * @return string
     */
    protected function formatUnitCode(BaseQuoteProductItem $item)
    {
        $unit = $this->productUnitLabelFormatter->format($item->getProductUnitCode());

        return $unit;
    }
}
