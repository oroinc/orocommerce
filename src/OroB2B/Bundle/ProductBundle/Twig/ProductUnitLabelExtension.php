<?php

namespace OroB2B\Bundle\ProductBundle\Twig;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class ProductUnitLabelExtension extends \Twig_Extension
{
    const NAME = 'orob2b_product_unit_label';

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $formatter;

    /**
     * @param ProductUnitLabelFormatter $formatter
     */
    public function __construct(ProductUnitLabelFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'orob2b_format_product_unit_label',
                [$this, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_short_product_unit_label',
                [$this, 'formatShort'],
                ['is_safe' => ['html']]
            )
        ];
    }

    /**
     * @param string $unitCode
     * @return string
     */
    public function format($unitCode)
    {
        return $this->formatter->format($unitCode);
    }

    /**
     * @param string $unitCode
     * @return string
     */
    public function formatShort($unitCode)
    {
        return $this->formatter->formatShort($unitCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
