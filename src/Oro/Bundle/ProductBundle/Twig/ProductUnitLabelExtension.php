<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;

/**
 * Provides TWIG functions for product unit label formatting.
 */
class ProductUnitLabelExtension extends \Twig_Extension
{
    const NAME = 'oro_product_unit_label';

    /** @var UnitLabelFormatterInterface */
    protected $unitLabelFormatter;

    /**
     * @param UnitLabelFormatterInterface $unitLabelFormatter
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
            new \Twig_SimpleFilter(
                'oro_format_short_product_unit_label',
                [$this, 'formatShort'],
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
}
