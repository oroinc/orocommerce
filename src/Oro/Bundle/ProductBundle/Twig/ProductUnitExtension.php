<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitPrecisionLabelFormatter;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides Twig filters to format product units and theirs labels:
 *   - oro_format_product_unit_label
 *   - oro_format_short_product_unit_label
 *   - oro_format_product_unit_value
 *   - oro_format_short_product_unit_value
 *   - oro_format_product_unit_code
 *   - oro_get_product_units_with_precision
 *
 * Provides a Twig function to check if product units are visible:
 *   - oro_is_unit_code_visible
 */
class ProductUnitExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_is_unit_code_visible', [$this, 'isUnitCodeVisible']),
            new TwigFunction('oro_format_product_unit_precision_label', [$this, 'formatUnitPrecisionLabel']),
            new TwigFunction(
                'oro_get_product_units_with_precision',
                [$this, 'getProductUnitsWithPrecision']
            ),
        ];
    }

    #[\Override]
    public function getFilters()
    {
        return [
            new TwigFilter('oro_format_product_unit_label', [$this, 'formatLabel']),
            new TwigFilter('oro_format_short_product_unit_label', [$this, 'formatLabelShort']),
            new TwigFilter('oro_format_product_unit_value', [$this, 'formatValue']),
            new TwigFilter('oro_format_short_product_unit_value', [$this, 'formatValueShort']),
            new TwigFilter('oro_format_product_unit_code', [$this, 'formatValueCode']),
        ];
    }

    /**
     * @param string $unitCode
     * @param bool $isShort
     * @param bool $isPlural
     * @return string
     */
    public function formatLabel($unitCode, $isShort = false, $isPlural = false)
    {
        return $this->getLabelFormatter()->format($unitCode, $isShort, $isPlural);
    }

    /**
     * @param string $unitCode
     * @param bool $isPlural
     * @return string
     */
    public function formatLabelShort($unitCode, $isPlural = false)
    {
        return $this->formatLabel($unitCode, true, $isPlural);
    }

    /**
     * @param float|int|null       $value
     * @param MeasureUnitInterface|null $unit
     *
     * @return string
     */
    public function formatValue($value, ?MeasureUnitInterface $unit = null)
    {
        return $this->getValueFormatter()->format($value, $unit);
    }

    /**
     * @param float|int|null       $value
     * @param MeasureUnitInterface|null $unit
     *
     * @return string
     */
    public function formatValueShort($value, ?MeasureUnitInterface $unit = null)
    {
        return $this->getValueFormatter()->formatShort($value, $unit);
    }

    /**
     * @param float|int $value
     * @param string    $unitCode
     * @param bool      $isShort
     *
     * @return string
     */
    public function formatValueCode($value, $unitCode, $isShort = false)
    {
        return $this->getValueFormatter()->formatCode($value, $unitCode, $isShort);
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function isUnitCodeVisible($code)
    {
        return $this->getUnitVisibility()->isUnitCodeVisible($code);
    }

    public function formatUnitPrecisionLabel(?string $unitCode, int $precision): string
    {
        if ($unitCode === null) {
            return '';
        }

        return $this->getUnitPrecisionLabelFormatter()->formatUnitPrecisionLabel($unitCode, $precision);
    }

    /**
     * @param Product|null $product
     *
     * @return array<string,int> Array of product unit codes with their default precision, e.g. ['kg' => 3, 'item' => 0]
     */
    public function getProductUnitsWithPrecision(?Product $product = null): array
    {
        if ($product !== null) {
            return $product->getAvailableUnitsPrecision();
        }

        /** @var ProductUnitsProvider $productUnitsProvider */
        $productUnitsProvider = $this->container->get('oro_product.provider.product_units_provider');

        return $productUnitsProvider->getAvailableProductUnitsWithPrecision();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_product.formatter.product_unit_value' => UnitValueFormatterInterface::class,
            'oro_product.formatter.product_unit_label' => UnitLabelFormatterInterface::class,
            'oro_product.formatter.product_unit_precision' => UnitLabelFormatterInterface::class,
            'oro_product.visibility.unit' => UnitVisibilityInterface::class,
            UnitPrecisionLabelFormatter::class,
            'oro_product.provider.product_units_provider' => ProductUnitsProvider::class,
        ];
    }

    private function getLabelFormatter(): UnitLabelFormatterInterface
    {
        return $this->container->get('oro_product.formatter.product_unit_label');
    }

    private function getValueFormatter(): UnitValueFormatterInterface
    {
        return $this->container->get('oro_product.formatter.product_unit_value');
    }

    private function getUnitVisibility(): UnitVisibilityInterface
    {
        return $this->container->get('oro_product.visibility.unit');
    }

    private function getUnitPrecisionLabelFormatter(): UnitPrecisionLabelFormatter
    {
        return $this->container->get(UnitPrecisionLabelFormatter::class);
    }
}
