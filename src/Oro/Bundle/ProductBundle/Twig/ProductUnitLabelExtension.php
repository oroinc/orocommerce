<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters to format product unit labels:
 *   - oro_format_product_unit_label
 *   - oro_format_short_product_unit_label
 */
class ProductUnitLabelExtension extends AbstractExtension
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
