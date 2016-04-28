<?php

namespace OroB2B\Bundle\ShippingBundle\Twig;

use OroB2B\Bundle\ProductBundle\Formatter\AbstractUnitValueFormatter;

class WeightUnitValueExtension extends \Twig_Extension
{
    const NAME = 'orob2b_weight_unit_value';

    /** @var AbstractUnitValueFormatter */
    protected $formatter;

    /**
     * @param AbstractUnitValueFormatter $formatter
     */
    public function __construct(AbstractUnitValueFormatter $formatter)
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
                'orob2b_format_weight_unit_value',
                [$this->formatter, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_short_weight_unit_value',
                [$this->formatter, 'formatShort'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_weight_unit_code',
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
