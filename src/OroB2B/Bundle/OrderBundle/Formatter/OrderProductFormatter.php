<?php

namespace OroB2B\Bundle\OrderBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class OrderProductFormatter
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
     * @param OrderProductItem $item
     * @return string
     */
    public function formatItem(OrderProductItem $item)
    {
        switch ($item->getPriceType()) {
            case OrderProductItem::PRICE_TYPE_BUNDLED:
                $transConstant = 'orob2b.order.orderproductitem.item_bundled';
                break;
            default:
                $transConstant = 'orob2b.order.orderproductitem.item';
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
     * @param OrderProductItem $item
     * @param string $default
     * @return string
     */
    protected function formatProductUnit(OrderProductItem $item, $default = '')
    {
        if (!$item->getProductUnit()) {
            return sprintf('%s %s', $item->getQuantity(), $item->getProductUnitCode());
        } elseif ($item->getQuantity()) {
            return $this->productUnitValueFormatter->format($item->getQuantity(), $item->getProductUnit());
        }

        return $default;
    }

    /**
     * @param OrderProductItem $item
     * @param string $default
     * @return string
     */
    protected function formatPrice(OrderProductItem $item, $default = '')
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
     * @param OrderProductItem $item
     * @return string
     */
    protected function formatUnitCode(OrderProductItem $item)
    {
        $unit = $this->productUnitLabelFormatter->format($item->getProductUnitCode());

        return $unit;
    }
}
