<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShippingMethodExtension extends \Twig_Extension
{
    const SHIPPING_METHOD_EXTENSION_NAME = 'oro_shipping_method';
    const DEFAULT_METHOD_CONFIG_TEMPLATE = 'OroShippingBundle:ShippingRule:shippingMethodWithOptions.html.twig';

    /**
     * @var ShippingMethodLabelFormatter
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected $configCache = [];

    /**
     * @param ShippingMethodLabelFormatter $shippingMethodLabelFormatter
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ShippingMethodLabelFormatter $shippingMethodLabelFormatter,
        EventDispatcherInterface $dispatcher
    ) {
        $this->shippingMethodLabelFormatter = $shippingMethodLabelFormatter;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::SHIPPING_METHOD_EXTENSION_NAME;
    }

    /**
     * @param string $shippingMethodName
     * @return string Shipping Method config template path
     */
    public function getShippingMethodConfigRenderData($shippingMethodName)
    {
        $event = new ShippingMethodConfigDataEvent($shippingMethodName);
        if (!array_key_exists($shippingMethodName, $this->configCache)) {
            $this->dispatcher->dispatch(ShippingMethodConfigDataEvent::NAME, $event);
            $this->configCache[$shippingMethodName] = $event->getTemplate() ?
                : static::DEFAULT_METHOD_CONFIG_TEMPLATE;
        }

        return $this->configCache[$shippingMethodName];
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
                'oro_shipping_method_config_template',
                [$this, 'getShippingMethodConfigRenderData']
            )
        ];
    }
}
