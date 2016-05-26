<?php

namespace OroB2B\Bundle\ShippingBundle\Twig;

use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

class ShippingOptionLabelExtension extends \Twig_Extension
{
    const NAME = 'orob2b_shipping_option_label';

    /** @var UnitLabelFormatter */
    protected $lengthUnitLabelFormatter;

    /** @var UnitLabelFormatter */
    protected $weightUnitLabelFormatter;

    /** @var UnitLabelFormatter */
    protected $freightClassLabelFormatter;

    /**
     * @param UnitLabelFormatter $lengthUnitLabelFormatter
     * @param UnitLabelFormatter $weightUnitLabelFormatter
     * @param UnitLabelFormatter $freightClassLabelFormatter
     */
    public function __construct(
        UnitLabelFormatter $lengthUnitLabelFormatter,
        UnitLabelFormatter $weightUnitLabelFormatter,
        UnitLabelFormatter $freightClassLabelFormatter
    ) {
        $this->lengthUnitLabelFormatter = $lengthUnitLabelFormatter;
        $this->weightUnitLabelFormatter = $weightUnitLabelFormatter;
        $this->freightClassLabelFormatter = $freightClassLabelFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'orob2b_length_unit_format_label',
                [$this->lengthUnitLabelFormatter, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_weight_unit_format_label',
                [$this->weightUnitLabelFormatter, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_freight_class_format_label',
                [$this->freightClassLabelFormatter, 'format'],
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
