<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;

class ProductUnitValueExtension extends \Twig_Extension
{
    const NAME = 'oro_product_unit_value';

    /**
     * @var UnitValueFormatterInterface
     */
    protected $formatter;

    /**
     * @param UnitValueFormatterInterface $formatter
     */
    public function __construct(UnitValueFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_format_product_unit_value',
                [$this->formatter, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_short_product_unit_value',
                [$this->formatter, 'formatShort'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_product_unit_code',
                [$this->formatter, 'formatCode'],
                ['is_safe' => ['html']]
            ),
        ];
    }
}
