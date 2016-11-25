<?php

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\OrderBundle\Formatter\ShippingMethodFormatter;
use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;

class OrderExtension extends \Twig_Extension
{
    const NAME = 'oro_order_order';

    /**
     * @var SourceDocumentFormatter
     */
    protected $sourceDocumentFormatter;

    /**
     * @var ShippingTrackingFormatter
     */
    protected $shippingTrackingFormatter;

    /**
     * OrderExtension constructor.
     *
     * @param SourceDocumentFormatter $sourceDocumentFormatter
     * @param ShippingTrackingFormatter $shippingTrackingFormatter
     */
    public function __construct(
        SourceDocumentFormatter $sourceDocumentFormatter,
        ShippingTrackingFormatter $shippingTrackingFormatter
    ) {
        $this->sourceDocumentFormatter = $sourceDocumentFormatter;
        $this->shippingTrackingFormatter = $shippingTrackingFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_order_format_source_document',
                [$this->sourceDocumentFormatter, 'format']
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_order_format_shipping_tracking_method',
                [$this->shippingTrackingFormatter, 'formatShippingTrackingMethod']
            ),
            new \Twig_SimpleFunction(
                'oro_order_format_shipping_tracking_link',
                [$this->shippingTrackingFormatter, 'formatShippingTrackingLink']
            )
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
