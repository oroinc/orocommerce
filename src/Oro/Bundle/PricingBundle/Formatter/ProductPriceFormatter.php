<?php

namespace Oro\Bundle\PricingBundle\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;

/**
 * Used to create formatted array of BaseProductPrice variables
 */
class ProductPriceFormatter
{
    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @var UnitLabelFormatterInterface
     */
    protected $unitLabelFormatter;

    /**
     * @var UnitValueFormatterInterface
     */
    protected $unitValueFormatter;

    /**
     * @param NumberFormatter $numberFormatter
     * @param UnitLabelFormatterInterface $unitLabelFormatter
     * @param UnitValueFormatterInterface $unitValueFormatter
     */
    public function __construct(
        NumberFormatter $numberFormatter,
        UnitLabelFormatterInterface $unitLabelFormatter,
        UnitValueFormatterInterface $unitValueFormatter
    ) {
        $this->numberFormatter = $numberFormatter;
        $this->unitLabelFormatter = $unitLabelFormatter;
        $this->unitValueFormatter = $unitValueFormatter;
    }

    /**
     * @param array $productsWithPrices
     * @return array
     */
    public function formatProducts(array $productsWithPrices)
    {
        $resultPrices = [];
        foreach ($productsWithPrices as $productId => $units) {
            $resultPrices[$productId] = $this->formatProductUnits($units);
        }

        return $resultPrices;
    }

    /**
     * @param array $units
     * @return array
     */
    public function formatProductUnits(array $units)
    {
        $productData = [];
        foreach ($units as $unit => $pricesData) {
            foreach ($pricesData as $priceData) {
                $quantity = $priceData['quantity'];
                $data = [
                    'price' => $priceData['price'],
                    'currency' => $priceData['currency'],
                    'unit' => $unit,
                    'quantity' => $quantity
                ];

                $index = sprintf('%s_%s', $unit, $quantity);
                $productData[$index] = $this->formatProductPriceData($data);
            }
        }
        return $productData;
    }

    /**
     * @param BaseProductPrice $price
     * @return array
     */
    public function formatProductPrice(BaseProductPrice $price)
    {
        $data = [
            'price' => $price->getPrice()->getValue(),
            'currency' => $price->getPrice()->getCurrency(),
            'unit' => $price->getUnit()->getCode(),
            'quantity' => $price->getQuantity()
        ];

        return $this->formatProductPriceData($data);
    }

    /**
     * @param array $data
     * @return array
     */
    public function formatProductPriceData(array $data)
    {
        return [
            'price' => $data['price'],
            'currency' => $data['currency'],
            'formatted_price' => $this->numberFormatter->formatCurrency($data['price'], $data['currency']),
            'unit' => $data['unit'],
            'formatted_unit' => $this->unitLabelFormatter->format($data['unit']),
            'quantity' => $data['quantity'],
            'quantity_with_unit' => $this->unitValueFormatter->formatCode($data['quantity'], $data['unit'])
        ];
    }
}
