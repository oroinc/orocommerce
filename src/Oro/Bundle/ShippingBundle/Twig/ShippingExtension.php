<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierCheckerInterface;
use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides Twig functions to retrieve shipping method configuration:
 *   - get_shipping_method_label
 *   - get_shipping_method_type_label
 *   - oro_shipping_method_with_type_label
 *   - oro_shipping_method_config_template
 *   - oro_shipping_method_enabled
 *
 * Provides Twig filters to format dimensional units:
 *   - oro_dimensions_unit_format_value
 *   - oro_dimensions_unit_format_value_short
 *   - oro_dimensions_unit_format_code
 *
 * Provides Twig filters to format weight units:
 *   - oro_weight_unit_format_label
 *   - oro_weight_unit_format_value
 *   - oro_weight_unit_format_value_short
 *   - oro_weight_unit_format_code
 *
 * Provides Twig filters to format length units and freight class:
 *   - oro_length_unit_format_label
 *   - oro_freight_class_format_label
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShippingExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const DEFAULT_METHOD_CONFIG_TEMPLATE
        = '@OroShipping/ShippingMethodsConfigsRule/shippingMethodWithOptions.html.twig';

    private ContainerInterface $container;
    private array $shippingMethodConfigCache = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('get_shipping_method_label', [$this, 'formatShippingMethodLabel']),
            new TwigFunction('get_shipping_method_type_label', [$this, 'formatShippingMethodTypeLabel']),
            new TwigFunction('oro_shipping_method_with_type_label', [$this, 'formatShippingMethodWithTypeLabel']),
            new TwigFunction('oro_shipping_method_config_template', [$this, 'getShippingMethodConfigRenderData']),
            new TwigFunction('oro_shipping_method_enabled', [$this, 'isShippingMethodEnabled'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('oro_dimensions_unit_format_value', [$this, 'formatDimensionsUnitValue']),
            new TwigFilter('oro_dimensions_unit_format_value_short', [$this, 'formatDimensionsUnitValueShort']),
            new TwigFilter('oro_dimensions_unit_format_code', [$this, 'formatDimensionsUnitValueCode']),
            new TwigFilter('oro_weight_unit_format_label', [$this, 'formatWeightUnitLabel']),
            new TwigFilter('oro_weight_unit_format_value', [$this, 'formatWeightUnitValue']),
            new TwigFilter('oro_weight_unit_format_value_short', [$this, 'formatWeightUnitValueShort']),
            new TwigFilter('oro_weight_unit_format_code', [$this, 'formatWeightUnitValueCode']),
            new TwigFilter('oro_length_unit_format_label', [$this, 'formatLengthUnitLabel']),
            new TwigFilter('oro_freight_class_format_label', [$this, 'formatFreightClassLabel']),
        ];
    }

    /**
     * @param string $shippingMethodName
     *
     * @return string
     */
    public function formatShippingMethodLabel($shippingMethodName)
    {
        return $this->getShippingMethodLabelFormatter()->formatShippingMethodLabel($shippingMethodName);
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
     * @param string $shippingMethodName
     *
     * @return string Shipping Method config template path
     */
    public function getShippingMethodConfigRenderData($shippingMethodName)
    {
        $event = new ShippingMethodConfigDataEvent($shippingMethodName);
        if (!\array_key_exists($shippingMethodName, $this->shippingMethodConfigCache)) {
            $this->getEventDispatcher()->dispatch($event, ShippingMethodConfigDataEvent::NAME);
            $template = $event->getTemplate();
            if (!$template) {
                $template = self::DEFAULT_METHOD_CONFIG_TEMPLATE;
            }
            $this->shippingMethodConfigCache[$shippingMethodName] = $template;
        }

        return $this->shippingMethodConfigCache[$shippingMethodName];
    }

    /**
     * @param string $methodIdentifier
     *
     * @return bool
     */
    public function isShippingMethodEnabled($methodIdentifier)
    {
        return $this->getShippingMethodChecker()->isEnabled($methodIdentifier);
    }

    /**
     * @param float|int|null       $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function formatDimensionsUnitValue($value, MeasureUnitInterface $unit = null)
    {
        return $this->getDimensionsUnitValueFormatter()->format($value, $unit);
    }

    /**
     * @param float|int|null       $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function formatDimensionsUnitValueShort($value, MeasureUnitInterface $unit = null)
    {
        return $this->getDimensionsUnitValueFormatter()->formatShort($value, $unit);
    }

    /**
     * @param float|int $value
     * @param string    $unitCode
     * @param bool      $isShort
     *
     * @return string
     */
    public function formatDimensionsUnitValueCode($value, $unitCode, $isShort = false)
    {
        return $this->getDimensionsUnitValueFormatter()->formatCode($value, $unitCode, $isShort);
    }

    /**
     * @param string $code
     * @param bool   $isShort
     * @param bool   $isPlural
     *
     * @return string
     */
    public function formatWeightUnitLabel($code, $isShort = false, $isPlural = false)
    {
        return $this->getWeightUnitLabelFormatter()->format($code, $isShort, $isPlural);
    }

    /**
     * @param float|int|null       $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function formatWeightUnitValue($value, MeasureUnitInterface $unit = null)
    {
        return $this->getWeightUnitValueFormatter()->format($value, $unit);
    }

    /**
     * @param float|int|null       $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function formatWeightUnitValueShort($value, MeasureUnitInterface $unit = null)
    {
        return $this->getWeightUnitValueFormatter()->formatShort($value, $unit);
    }

    /**
     * @param float|int $value
     * @param string    $unitCode
     * @param bool      $isShort
     *
     * @return string
     */
    public function formatWeightUnitValueCode($value, $unitCode, $isShort = false)
    {
        return $this->getWeightUnitValueFormatter()->formatCode($value, $unitCode, $isShort);
    }

    /**
     * @param string $code
     * @param bool   $isShort
     * @param bool   $isPlural
     *
     * @return string
     */
    public function formatLengthUnitLabel($code, $isShort = false, $isPlural = false)
    {
        return $this->getLengthUnitLabelFormatter()->format($code, $isShort, $isPlural);
    }

    /**
     * @param string $code
     * @param bool   $isShort
     * @param bool   $isPlural
     *
     * @return string
     */
    public function formatFreightClassLabel($code, $isShort = false, $isPlural = false)
    {
        return $this->getFreightClassLabelFormatter()->format($code, $isShort, $isPlural);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_shipping.formatter.shipping_method_label' => ShippingMethodLabelFormatter::class,
            'oro_shipping.checker.shipping_method_enabled' => ShippingMethodEnabledByIdentifierCheckerInterface::class,
            'oro_shipping.formatter.dimensions_unit_value' => UnitValueFormatterInterface::class,
            'oro_shipping.formatter.weight_unit_label' => UnitLabelFormatterInterface::class,
            'oro_shipping.formatter.weight_unit_value' => UnitValueFormatterInterface::class,
            'oro_shipping.formatter.length_unit_label' => UnitLabelFormatterInterface::class,
            'oro_shipping.formatter.freight_class_label' => UnitLabelFormatterInterface::class,
            EventDispatcherInterface::class,
        ];
    }

    private function getShippingMethodLabelFormatter(): ShippingMethodLabelFormatter
    {
        return $this->container->get('oro_shipping.formatter.shipping_method_label');
    }

    private function getShippingMethodChecker(): ShippingMethodEnabledByIdentifierCheckerInterface
    {
        return $this->container->get('oro_shipping.checker.shipping_method_enabled');
    }

    private function getDimensionsUnitValueFormatter(): UnitValueFormatterInterface
    {
        return $this->container->get('oro_shipping.formatter.dimensions_unit_value');
    }

    private function getWeightUnitLabelFormatter(): UnitLabelFormatterInterface
    {
        return $this->container->get('oro_shipping.formatter.weight_unit_label');
    }

    private function getWeightUnitValueFormatter(): UnitValueFormatterInterface
    {
        return $this->container->get('oro_shipping.formatter.weight_unit_value');
    }

    private function getLengthUnitLabelFormatter(): UnitLabelFormatterInterface
    {
        return $this->container->get('oro_shipping.formatter.length_unit_label');
    }

    private function getFreightClassLabelFormatter(): UnitLabelFormatterInterface
    {
        return $this->container->get('oro_shipping.formatter.freight_class_label');
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->container->get(EventDispatcherInterface::class);
    }
}
