<?php

namespace Oro\Bundle\SaleBundle\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Model\BaseQuoteProductItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Facade for formatting operations
 * use translator to translate variables, delegates formatting responsibility to internal formatters
 */
class QuoteProductFormatter
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var UnitValueFormatterInterface
     */
    protected $productUnitValueFormatter;

    /**
     * @var UnitLabelFormatterInterface
     */
    protected $productUnitLabelFormatter;

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    public function __construct(
        TranslatorInterface $translator,
        NumberFormatter $numberFormatter,
        UnitValueFormatterInterface $productUnitValueFormatter,
        UnitLabelFormatterInterface $productUnitLabelFormatter
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
        }

        return $this->translator->trans('N/A');
    }

    /**
     * @param string $typeLabel
     * @return string
     */
    public function formatTypeLabel($typeLabel)
    {
        $translationKey = sprintf('oro.sale.quoteproduct.type.%s', $typeLabel);

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
            'oro.sale.quoteproductrequest.item',
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
                $transConstant = 'oro.sale.quoteproductoffer.item_bundled';
                break;
            default:
                $transConstant = 'oro.sale.quoteproductoffer.item';
        }

        $str = $this->translator->trans(
            $transConstant,
            [
                '%count%'   => (int)$item->isAllowIncrements(),
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
        }

        if ($item->getQuantity()) {
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
        return $this->productUnitLabelFormatter->format($item->getProductUnitCode());
    }
}
