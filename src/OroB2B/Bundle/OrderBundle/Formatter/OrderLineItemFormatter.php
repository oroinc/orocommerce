<?php

namespace OroB2B\Bundle\OrderBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class OrderLineItemFormatter
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
     * @param OrderLineItem $item
     * @return string
     */
    public function formatItem(OrderLineItem $item)
    {
        if ($item->getPriceType() === OrderLineItem::PRICE_TYPE_BUNDLED) {
            $transConstant = 'orob2b.order.orderlineitem.item_bundled';
        } else {
            $transConstant = 'orob2b.order.orderlineitem.item';
        }

        $str = $this->translator->trans(
            $transConstant,
            [
                '{units}'   => $this->formatProductUnit($item),
                '{price}'   => $this->formatPrice($item),
                '{unit}'    => $this->formatUnitCode($item),
            ]
        );

        return $str;
    }

    /**
     * @param string $priceTypeLabel
     * @return string
     */
    public function formatPriceTypeLabel($priceTypeLabel)
    {
        $translationKey = sprintf('orob2b.order.orderlineitem.price_type.%s', $priceTypeLabel);

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

    /**
     * @param OrderLineItem $item
     * @param string $default
     * @return string
     */
    protected function formatProductUnit(OrderLineItem $item, $default = '')
    {
        if (!$item->getProductUnit()) {
            return sprintf('%s %s', $item->getQuantity(), $item->getProductUnitCode());
        } elseif ($item->getQuantity()) {
            return $this->productUnitValueFormatter->format($item->getQuantity(), $item->getProductUnit());
        }

        return $default;
    }

    /**
     * @param OrderLineItem $item
     * @param string $default
     * @return string
     */
    protected function formatPrice(OrderLineItem $item, $default = '')
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
     * @param OrderLineItem $item
     * @return string
     */
    protected function formatUnitCode(OrderLineItem $item)
    {
        $unit = $this->productUnitLabelFormatter->format($item->getProductUnitCode());

        return $unit;
    }
}
