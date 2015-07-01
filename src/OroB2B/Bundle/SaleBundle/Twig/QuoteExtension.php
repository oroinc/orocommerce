<?php

namespace OroB2B\Bundle\SaleBundle\Twig;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

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
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @param TranslatorInterface $translator
     * @param NumberFormatter $numberFormatter
     * @param ProductUnitValueFormatter $productUnitValueFormatter
     */
    public function __construct(
        TranslatorInterface $translator,
        NumberFormatter $numberFormatter,
        ProductUnitValueFormatter $productUnitValueFormatter
    ) {
        $this->translator                   = $translator;
        $this->numberFormatter              = $numberFormatter;
        $this->productUnitValueFormatter    = $productUnitValueFormatter;
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
        $units = $item->getProductUnit()
            ? $this->productUnitValueFormatter->format($item->getQuantity(), $item->getProductUnit())
            : sprintf('%s %s', $item->getQuantity(), $item->getProductUnitCode())
        ;

        $price = $this->numberFormatter->formatCurrency(
            $item->getPrice()->getValue(),
            $item->getPrice()->getCurrency()
        );
        $unit = $this->translator->trans(
            sprintf('orob2b.product_unit.%s.label.full', $item->getProductUnitCode())
        );

        $str = $this->translator->trans(
            'orob2b.sale.quoteproductoffer.item',
            [
                '{units}'   => $units,
                '{price}'   => $price,
                '{unit}'    => $unit,
            ]
        );

        return $str;
    }


    /**
     * @param QuoteProductRequest $item
     * @return string
     */
    public function formatProductRequest(QuoteProductRequest $item)
    {
        $units = $item->getProductUnit()
            ? $this->productUnitValueFormatter->format($item->getQuantity(), $item->getProductUnit())
            : sprintf('%s %s', $item->getQuantity(), $item->getProductUnitCode())
        ;

        $price = $this->numberFormatter->formatCurrency(
            $item->getPrice()->getValue(),
            $item->getPrice()->getCurrency()
        );
        $unit = $this->translator->trans(
            sprintf('orob2b.product_unit.%s.label.full', $item->getProductUnitCode())
        );

        $str = $this->translator->trans(
            'orob2b.sale.quoteproductrequest.item',
            [
                '{units}'   => $units,
                '{price}'   => $price,
                '{unit}'    => $unit,
            ]
        );

        return $str;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
