<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters to format length units, weight units, and freight class:
 *   - oro_length_unit_format_label
 *   - oro_weight_unit_format_label
 *   - oro_freight_class_format_label
 */
class ShippingOptionLabelExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    const NAME = 'oro_shipping_option_label';

    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return UnitLabelFormatterInterface
     */
    protected function getLengthUnitLabelFormatter()
    {
        return $this->container->get('oro_shipping.formatter.length_unit_label');
    }

    /**
     * @return UnitLabelFormatterInterface
     */
    protected function getWeightUnitLabelFormatter()
    {
        return $this->container->get('oro_shipping.formatter.weight_unit_label');
    }

    /**
     * @return UnitLabelFormatterInterface
     */
    protected function getFreightClassLabelFormatter()
    {
        return $this->container->get('oro_shipping.formatter.freight_class_label');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'oro_length_unit_format_label',
                [$this, 'formatLengthUnit']
            ),
            new TwigFilter(
                'oro_weight_unit_format_label',
                [$this, 'formatWeightUnit']
            ),
            new TwigFilter(
                'oro_freight_class_format_label',
                [$this, 'formatFreightClass']
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

    /**
     * @param string $code
     * @param bool   $isShort
     * @param bool   $isPlural
     *
     * @return string
     */
    public function formatLengthUnit($code, $isShort = false, $isPlural = false)
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
    public function formatWeightUnit($code, $isShort = false, $isPlural = false)
    {
        return $this->getWeightUnitLabelFormatter()->format($code, $isShort, $isPlural);
    }

    /**
     * @param string $code
     * @param bool   $isShort
     * @param bool   $isPlural
     *
     * @return string
     */
    public function formatFreightClass($code, $isShort = false, $isPlural = false)
    {
        return $this->getFreightClassLabelFormatter()->format($code, $isShort, $isPlural);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_shipping.formatter.length_unit_label' => UnitLabelFormatterInterface::class,
            'oro_shipping.formatter.weight_unit_label' => UnitLabelFormatterInterface::class,
            'oro_shipping.formatter.freight_class_label' => UnitLabelFormatterInterface::class,
        ];
    }
}
