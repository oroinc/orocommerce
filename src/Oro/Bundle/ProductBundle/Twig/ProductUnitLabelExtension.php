<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters to format product unit labels:
 *   - oro_format_product_unit_label
 *   - oro_format_short_product_unit_label
 */
class ProductUnitLabelExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    const NAME = 'oro_product_unit_label';

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'oro_format_product_unit_label',
                [$this, 'format']
            ),
            new TwigFilter(
                'oro_format_short_product_unit_label',
                [$this, 'formatShort']
            ),
        ];
    }

    /**
     * @param string $unitCode
     * @param bool $isShort
     * @param bool $isPlural
     * @return string
     */
    public function format($unitCode, $isShort = false, $isPlural = false)
    {
        return $this->container->get('oro_product.formatter.product_unit_label')
            ->format($unitCode, $isShort, $isPlural);
    }

    /**
     * @param string $unitCode
     * @param bool $isPlural
     * @return string
     */
    public function formatShort($unitCode, $isPlural = false)
    {
        return $this->format($unitCode, true, $isPlural);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_product.formatter.product_unit_label' => UnitLabelFormatterInterface::class,
        ];
    }
}
