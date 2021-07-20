<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierCheckerInterface;
use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to retrieve shipping method configuration:
 *   - get_shipping_method_label
 *   - get_shipping_method_type_label
 *   - oro_shipping_method_with_type_label
 *   - oro_shipping_method_config_template
 *   - oro_shipping_method_enabled
 */
class ShippingMethodExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    const SHIPPING_METHOD_EXTENSION_NAME = 'oro_shipping_method';
    const DEFAULT_METHOD_CONFIG_TEMPLATE
        = 'OroShippingBundle:ShippingMethodsConfigsRule:shippingMethodWithOptions.html.twig';

    /** @var ContainerInterface */
    private $container;

    /** @var array */
    protected $configCache = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
     *
     * @return string Shipping Method config template path
     */
    public function getShippingMethodConfigRenderData($shippingMethodName)
    {
        $event = new ShippingMethodConfigDataEvent($shippingMethodName);
        if (!array_key_exists($shippingMethodName, $this->configCache)) {
            $this->container->get('event_dispatcher')->dispatch($event, ShippingMethodConfigDataEvent::NAME);
            $template = $event->getTemplate();
            if (!$template) {
                $template = static::DEFAULT_METHOD_CONFIG_TEMPLATE;
            }
            $this->configCache[$shippingMethodName] = $template;
        }

        return $this->configCache[$shippingMethodName];
    }

    /**
     * @param string $methodIdentifier
     *
     * @return bool
     */
    public function isShippingMethodEnabled($methodIdentifier)
    {
        return $this->container->get('oro_shipping.checker.shipping_method_enabled')->isEnabled($methodIdentifier);
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        $shippingMethodLabelFormatter = $this->container->get('oro_shipping.formatter.shipping_method_label');

        return [
            new TwigFunction(
                'get_shipping_method_label',
                [$shippingMethodLabelFormatter, 'formatShippingMethodLabel']
            ),
            new TwigFunction(
                'get_shipping_method_type_label',
                [$shippingMethodLabelFormatter, 'formatShippingMethodTypeLabel']
            ),
            new TwigFunction(
                'oro_shipping_method_with_type_label',
                [$shippingMethodLabelFormatter, 'formatShippingMethodWithTypeLabel']
            ),
            new TwigFunction(
                'oro_shipping_method_config_template',
                [$this, 'getShippingMethodConfigRenderData']
            ),
            new TwigFunction(
                'oro_shipping_method_enabled',
                [$this, 'isShippingMethodEnabled']
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_shipping.formatter.shipping_method_label' => ShippingMethodLabelFormatter::class,
            'event_dispatcher' => EventDispatcherInterface::class,
            'oro_shipping.checker.shipping_method_enabled' => ShippingMethodEnabledByIdentifierCheckerInterface::class,
        ];
    }
}
