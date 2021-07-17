<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters to format product units:
 *   - oro_format_product_unit_value
 *   - oro_format_short_product_unit_value
 *   - oro_format_product_unit_code
 */
class ProductUnitValueExtension extends AbstractExtension implements ServiceSubscriberInterface
{
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
        return $this->container->get('oro_product.formatter.product_unit_value');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'oro_format_product_unit_value',
                [$this, 'format']
            ),
            new TwigFilter(
                'oro_format_short_product_unit_value',
                [$this, 'formatShort']
            ),
            new TwigFilter(
                'oro_format_product_unit_code',
                [$this, 'formatCode']
            ),
        ];
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
            'oro_product.formatter.product_unit_value' => UnitValueFormatterInterface::class,
        ];
    }
}
