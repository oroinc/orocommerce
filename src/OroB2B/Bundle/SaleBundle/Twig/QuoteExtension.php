<?php

namespace OroB2B\Bundle\SaleBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductTypeFormatter;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;
use OroB2B\Bundle\SaleBundle\Model\BaseQuoteProductItem;

class QuoteExtension extends \Twig_Extension
{
    const NAME = 'orob2b_sale_quote';

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
     * @var QuoteProductTypeFormatter
     */
    protected $quoteProductTypeFormatter;

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @param TranslatorInterface $translator
     * @param NumberFormatter $numberFormatter
     * @param ProductUnitValueFormatter $productUnitValueFormatter
     * @param ProductUnitLabelFormatter $productUnitLabelFormatter
     * @param QuoteProductTypeFormatter $quoteProductTypeFormatter
     */
    public function __construct(
        TranslatorInterface $translator,
        NumberFormatter $numberFormatter,
        ProductUnitValueFormatter $productUnitValueFormatter,
        ProductUnitLabelFormatter $productUnitLabelFormatter,
        QuoteProductTypeFormatter $quoteProductTypeFormatter
    ) {
        $this->translator                   = $translator;
        $this->numberFormatter              = $numberFormatter;
        $this->productUnitValueFormatter    = $productUnitValueFormatter;
        $this->productUnitLabelFormatter    = $productUnitLabelFormatter;
        $this->quoteProductTypeFormatter    = $quoteProductTypeFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'orob2b_format_sale_quote_product_offer',
                [$this, 'formatProductOffer'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_sale_quote_product_type',
                [$this, 'formatProductType'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_sale_quote_product_request',
                [$this, 'formatProductRequest'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param QuoteProductOffer $item
     * @return string
     */
    public function formatProductOffer(QuoteProductOffer $item)
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
            (int)$item->getAllowIncrements(),
            [
                '{units}'   => $this->formatProductUnit($item),
                '{price}'   => $this->formatPrice($item),
                '{unit}'    => $this->formatUnitCode($item),
            ]
        );

        return $str;
    }

    /**
     * @param int $type
     * @return string
     */
    public function formatProductType($type)
    {
        $types = QuoteProduct::getTypes();

        if (isset($types[$type])) {
            return $this->quoteProductTypeFormatter->formatTypeLabel($types[$type]);
        } else {
            return $this->translator->trans('N/A');
        }
    }

    /**
     * @param QuoteProductRequest $item
     * @return string
     */
    public function formatProductRequest(QuoteProductRequest $item)
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
