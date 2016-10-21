<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodTrackingLinkFormatter;

class ShippingMethodExtension extends \Twig_Extension
{
    const SHIPPING_METHOD_EXTENSION_NAME = 'oro_shipping_method';

    /**
     * @var ShippingMethodLabelFormatter
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @var ShippingMethodTrackingLinkFormatter
     */
    protected $shippingMethodTrackingLinkFormatter;

    /**
     * @param ShippingMethodLabelFormatter $shippingMethodLabelFormatter
     * @param ShippingMethodTrackingLinkFormatter $shippingMethodTrackingLinkFormatter
     */
    public function __construct(
        ShippingMethodLabelFormatter $shippingMethodLabelFormatter,
        ShippingMethodTrackingLinkFormatter $shippingMethodTrackingLinkFormatter
    ) {
        $this->shippingMethodLabelFormatter = $shippingMethodLabelFormatter;
        $this->shippingMethodTrackingLinkFormatter = $shippingMethodTrackingLinkFormatter;
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
            ),
            new \Twig_SimpleFunction(
                'get_shipping_method_tracking_link',
                [$this->shippingMethodTrackingLinkFormatter, 'formatShippingTrackingLink']
            )
        ];
    }
}
