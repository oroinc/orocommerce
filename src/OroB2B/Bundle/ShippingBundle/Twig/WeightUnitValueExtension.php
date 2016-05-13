<?php

namespace OroB2B\Bundle\ShippingBundle\Twig;

use OroB2B\Bundle\ProductBundle\Formatter\UnitValueFormatter;

class WeightUnitValueExtension extends \Twig_Extension
{
    const NAME = 'orob2b_weight_unit_value';

    /** @var UnitValueFormatter */
    protected $formatter;

    /**
     * @param UnitValueFormatter $formatter
     */
    public function __construct(UnitValueFormatter $formatter)
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
                'orob2b_weight_unit_format_value',
                [$this->formatter, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_weight_unit_format_value_short',
                [$this->formatter, 'formatShort'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_weight_unit_format_code',
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
