<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;

class ShippingMethodExtension extends \Twig_Extension
{
    const SHIPPING_METHOD_EXTENSION_NAME = 'oro_shipping_method';
    const DEFAULT_METHOD_CONFIG_TEMPLATE
        = 'OroShippingBundle:ShippingMethodsConfigsRule:shippingMethodWithOptions.html.twig';

    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $configCache = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ShippingMethodLabelFormatter
     */
    protected function getShippingMethodLabelFormatter()
    {
        return $this->container->get('oro_shipping.formatter.shipping_method_label');
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function getDispatcher()
    {
        return $this->container->get('event_dispatcher');
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
            $this->getDispatcher()->dispatch(ShippingMethodConfigDataEvent::NAME, $event);
            $template = $event->getTemplate();
            if (!$template) {
                $template = static::DEFAULT_METHOD_CONFIG_TEMPLATE;
            }
            $this->configCache[$shippingMethodName] = $template;
        }

        return $this->configCache[$shippingMethodName];
    }

    /**
     * @param string $shippingMethodName
     *
     * @return string
     */
    public function formatShippingMethodLabel($shippingMethodName)
    {
        return $this->getShippingMethodLabelFormatter()
            ->formatShippingMethodLabel($shippingMethodName);
    }

    /**
     * @param string $shippingMethodName
     * @param string $shippingTypeName
     *
     * @return string
     */
    public function formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName)
    {
        return $this->getShippingMethodLabelFormatter()
            ->formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName);
    }

    /**
     * @param string $shippingMethodName
     * @param string $shippingTypeName
     *
     * @return string
     */
    public function formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName)
    {
        return $this->getShippingMethodLabelFormatter()
            ->formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'get_shipping_method_label',
                [$this, 'formatShippingMethodLabel']
            ),
            new \Twig_SimpleFunction(
                'get_shipping_method_type_label',
                [$this, 'formatShippingMethodTypeLabel']
            ),
            new \Twig_SimpleFunction(
                'oro_shipping_method_with_type_label',
                [$this, 'formatShippingMethodWithTypeLabel']
            ),
            new \Twig_SimpleFunction(
                'oro_shipping_method_config_template',
                [$this, 'getShippingMethodConfigRenderData']
            )
        ];
    }
}
