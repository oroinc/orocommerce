<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

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
        ];
    }

    /**
     * @param string $unitCode
     * @param bool $isShort
     * @param bool $isPlural
     * @return string
     */
    public function format($unitCode, $isShort = false, $isPlural = false)
    {
        return $this->formatter->format($unitCode, $isShort, $isPlural);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
