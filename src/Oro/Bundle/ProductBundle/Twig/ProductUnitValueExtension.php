<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;

class ProductUnitValueExtension extends \Twig_Extension
{
    const NAME = 'oro_product_unit_value';

    /**
     * @var ProductUnitValueFormatter
     */
    protected $formatter;

    /**
     * @var SingleUnitModeService
     */
    protected $unitModeProvider;

    /**
     * @param ProductUnitValueFormatter $formatter
     * @param SingleUnitModeService $unitModeProvider
     */
    public function __construct(ProductUnitValueFormatter $formatter, SingleUnitModeService $unitModeProvider)
    {
        $this->formatter = $formatter;
        $this->unitModeProvider = $unitModeProvider;
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
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_is_single_unit_mode',
                [$this->unitModeProvider, 'isSingleUnitMode']
            ),
            new \Twig_SimpleFunction(
                'oro_is_single_unit_mode_code_visible',
                [$this->unitModeProvider, 'isSingleUnitModeCodeVisible']
            ),
            new \Twig_SimpleFunction(
                'oro_is_product_primary_unit_single_and_default',
                [$this->unitModeProvider, 'isProductPrimaryUnitSingleAndDefault']
            )
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
