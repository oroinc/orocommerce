<?php

namespace OroB2B\Bundle\ShippingBundle\Twig;

use OroB2B\Bundle\ShippingBundle\Formatter\LengthUnitLabelFormatter;
use OroB2B\Bundle\ShippingBundle\Formatter\WeightUnitLabelFormatter;
use OroB2B\Bundle\ShippingBundle\Formatter\FreightClassLabelFormatter;

class ShippingOptionLabelExtension extends \Twig_Extension
{
    const NAME = 'orob2b_shipping_option_label';

    /** @var LengthUnitLabelFormatter */
    protected $lengthUnitLabelFormatter;

    /** @var WeightUnitLabelFormatter */
    protected $weightUnitLabelFormatter;

    /** @var FreightClassLabelFormatter */
    protected $freightClassLabelFormatter;

    /**
     * @param LengthUnitLabelFormatter $lengthUnitLabelFormatter
     * @param WeightUnitLabelFormatter $weightUnitLabelFormatter
     * @param FreightClassLabelFormatter $freightClassLabelFormatter
     */
    public function __construct(
        LengthUnitLabelFormatter $lengthUnitLabelFormatter,
        WeightUnitLabelFormatter $weightUnitLabelFormatter,
        FreightClassLabelFormatter $freightClassLabelFormatter
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
                'orob2b_format_length_unit_label',
                [$this->lengthUnitLabelFormatter, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_weight_unit_label',
                [$this->weightUnitLabelFormatter, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'orob2b_format_freight_class_label',
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
