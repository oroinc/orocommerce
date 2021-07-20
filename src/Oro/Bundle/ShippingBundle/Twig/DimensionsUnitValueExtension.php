<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters to format dimensional units:
 *   - oro_dimensions_unit_format_value
 *   - oro_dimensions_unit_format_value_short
 *   - oro_dimensions_unit_format_code
 */
class DimensionsUnitValueExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    const NAME = 'oro_dimensions_unit_value';

    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return UnitValueFormatterInterface
     */
    protected function getFormatter()
    {
        return $this->container->get('oro_shipping.formatter.dimensions_unit_value');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'oro_dimensions_unit_format_value',
                [$this, 'format']
            ),
            new TwigFilter(
                'oro_dimensions_unit_format_value_short',
                [$this, 'formatShort']
            ),
            new TwigFilter(
                'oro_dimensions_unit_format_code',
                [$this, 'formatCode']
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
     * @param float|int|null       $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function format($value, MeasureUnitInterface $unit = null)
    {
        return $this->getFormatter()->format($value, $unit);
    }

    /**
     * @param float|int|null       $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function formatShort($value, MeasureUnitInterface $unit = null)
    {
        return $this->getFormatter()->formatShort($value, $unit);
    }

    /**
     * @param float|int $value
     * @param string    $unitCode
     * @param bool      $isShort
     *
     * @return string
     */
    public function formatCode($value, $unitCode, $isShort = false)
    {
        return $this->getFormatter()->formatCode($value, $unitCode, $isShort);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_shipping.formatter.dimensions_unit_value' => UnitValueFormatterInterface::class,
        ];
    }
}
