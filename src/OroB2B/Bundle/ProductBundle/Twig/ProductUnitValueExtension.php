<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

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
                [$this->formatter, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_short_product_unit_value',
                [$this->formatter, 'formatShort'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_product_unit_code',
                [$this->formatter, 'formatCode'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
