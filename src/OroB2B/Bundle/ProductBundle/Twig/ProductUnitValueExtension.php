<?php

namespace OroB2B\Bundle\ProductBundle\Twig;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class ProductUnitValueExtension extends \Twig_Extension
{
    const NAME = 'orob2b_product_unit_value';

    /**
     * @var ProductUnitValueFormatter
     */
    protected $formatter;

    /**
     * @param ProductUnitValueFormatter $formatter
     */
    public function __construct(ProductUnitValueFormatter $formatter)
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
                'orob2b_format_product_unit_value',
                [$this, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_short_product_unit_value',
                [$this, 'formatShort'],
                ['is_safe' => ['html']]
            )
        ];
    }

    /**
     * @param float|integer $value
     * @param ProductUnit $unit
     * @return string
     */
    public function format($value, ProductUnit $unit)
    {
        return $this->formatter->format($value, $unit);
    }

    /**
     * @param float|integer $value
     * @param ProductUnit $unit
     * @return string
     */
    public function formatShort($value, ProductUnit $unit)
    {
        return $this->formatter->formatShort($value, $unit);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
