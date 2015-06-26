<?php

namespace OroB2B\Bundle\RFPAdminBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

class RequestExtension extends \Twig_Extension
{
    const NAME = 'orob2b_rfpadmin_request';

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
                'orob2b_format_rfpadmin_request_product_item',
                [$this, 'formatProductItem'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param RequestProductItem $item
     * @return string
     */
    public function formatProductItem(RequestProductItem $item)
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
            'orob2b.rfpadmin.requestproductitem.item',
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
