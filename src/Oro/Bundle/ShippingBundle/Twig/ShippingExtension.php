<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
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
     * {@inheritDoc}
     */
    public function getFunctions(): array
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
     * {@inheritDoc}
     */
    public function getFilters(): array
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

    public function formatShippingMethodLabel(
        ?string $shippingMethod,
        Organization|int|null $organization = null
    ): string {
        return $this->getShippingMethodLabelFormatter()->formatShippingMethodLabel(
            $shippingMethod,
            \is_int($organization) ? $this->getOrganization($organization) : $organization
        );
    }

    public function formatShippingMethodTypeLabel(
        ?string $shippingMethod,
        ?string $shippingMethodType,
        Organization|int|null $organization = null
    ): string {
        return $this->getShippingMethodLabelFormatter()->formatShippingMethodTypeLabel(
            $shippingMethod,
            $shippingMethodType,
            \is_int($organization) ? $this->getOrganization($organization) : $organization
        );
    }

    public function formatShippingMethodWithTypeLabel(
        ?string $shippingMethod,
        ?string $shippingMethodType,
        Organization|int|null $organization = null
    ): string {
        return $this->getShippingMethodLabelFormatter()->formatShippingMethodWithTypeLabel(
            $shippingMethod,
            $shippingMethodType,
            \is_int($organization) ? $this->getOrganization($organization) : $organization
        );
    }

    /**
     * Gets shipping method config template path.
     */
    public function getShippingMethodConfigRenderData(string $shippingMethod): string
    {
        $event = new ShippingMethodConfigDataEvent($shippingMethod);
        if (!\array_key_exists($shippingMethod, $this->shippingMethodConfigCache)) {
            $this->getEventDispatcher()->dispatch($event, ShippingMethodConfigDataEvent::NAME);
            $template = $event->getTemplate();
            if (!$template) {
                $template = self::DEFAULT_METHOD_CONFIG_TEMPLATE;
            }
            $this->shippingMethodConfigCache[$shippingMethod] = $template;
        }

        return $this->shippingMethodConfigCache[$shippingMethod];
    }

    public function isShippingMethodEnabled(string $methodIdentifier): bool
    {
        return $this->getShippingMethodChecker()->isEnabled($methodIdentifier);
    }

    /**
     * @param float|int|null       $value
     * @param MeasureUnitInterface|null $unit
     *
     * @return string
     */
    public function formatDimensionsUnitValue($value, MeasureUnitInterface $unit = null)
    {
        return $this->getDimensionsUnitValueFormatter()->format($value, $unit);
    }

    /**
     * @param float|int|null       $value
     * @param MeasureUnitInterface|null $unit
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
     * @param MeasureUnitInterface|null $unit
     *
     * @return string
     */
    public function formatWeightUnitValue($value, MeasureUnitInterface $unit = null)
    {
        return $this->getWeightUnitValueFormatter()->format($value, $unit);
    }

    /**
     * @param float|int|null       $value
     * @param MeasureUnitInterface|null $unit
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
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
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
            DoctrineHelper::class
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

    private function getDoctrineHelper(): DoctrineHelper
    {
        return $this->container->get(DoctrineHelper::class);
    }

    private function getOrganization(int $organizationId): Organization
    {
        return $this->getDoctrineHelper()->getEntityReference(Organization::class, $organizationId);
    }
}
