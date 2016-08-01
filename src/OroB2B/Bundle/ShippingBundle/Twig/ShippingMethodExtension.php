<?php

namespace OroB2B\Bundle\ShippingBundle\Twig;

use OroB2B\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;

class ShippingMethodExtension extends \Twig_Extension
{
    const SHIPPING_METHOD_EXTENSION_NAME = 'orob2b_shipping_method';

    /**
     * @var ShippingMethodLabelFormatter
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @param ShippingMethodLabelFormatter $shippingMethodLabelFormatter
     */
    public function __construct(
        ShippingMethodLabelFormatter $shippingMethodLabelFormatter
    ) {
        $this->shippingMethodLabelFormatter = $shippingMethodLabelFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::SHIPPING_METHOD_EXTENSION_NAME;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'get_shipping_method_label',
                [$this->shippingMethodLabelFormatter, 'formatShippingMethodLabel']
            ),
            new \Twig_SimpleFunction(
                'get_shipping_method_type_label',
                [$this->shippingMethodLabelFormatter, 'formatShippingMethodTypeLabel']
            )
        ];
    }
}
