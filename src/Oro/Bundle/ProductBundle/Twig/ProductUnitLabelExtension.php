<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds Twig filter with name oro_format_product_unit_label
 * delegates formatting to UnitLabelFormatter
 */
class ProductUnitLabelExtension extends \Twig_Extension
{
    const NAME = 'oro_product_unit_label';

    /** @var UnitLabelFormatterInterface */
    protected $unitLabelFormatter;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(UnitLabelFormatterInterface $unitLabelFormatter)
    {
        $this->unitLabelFormatter = $unitLabelFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_format_product_unit_label',
                [$this, 'format'],
                ['is_safe' => ['html']]
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
        return $this->unitLabelFormatter->format($unitCode, $isShort, $isPlural);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
