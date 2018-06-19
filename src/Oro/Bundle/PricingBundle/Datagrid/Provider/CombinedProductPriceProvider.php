<?php

namespace Oro\Bundle\PricingBundle\Datagrid\Provider;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter;

/**
 * @todo BB-14587 remove this class, move it's logic if needed to ProductPriceProvider
 */
class CombinedProductPriceProvider implements CombinedProductPriceProviderInterface
{
    /**
     * @var CombinedProductPriceRepository
     */
    private $combinedProductPriceRepository;

    /**
     * @var NumberFormatter
     */
    private $numberFormatter;

    /**
     * @var UnitLabelFormatter
     */
    private $unitLabelFormatter;

    /**
     * @var UnitValueFormatter
     */
    private $unitValueFormatter;

    /**
     * @param CombinedProductPriceRepository $combinedProductPriceRepository
     * @param NumberFormatter                $numberFormatter
     * @param UnitLabelFormatter             $unitLabelFormatter
     * @param UnitValueFormatter             $unitValueFormatter
     */
    public function __construct(
        CombinedProductPriceRepository $combinedProductPriceRepository,
        NumberFormatter $numberFormatter,
        UnitLabelFormatter $unitLabelFormatter,
        UnitValueFormatter $unitValueFormatter
    ) {
        $this->combinedProductPriceRepository = $combinedProductPriceRepository;
        $this->numberFormatter                = $numberFormatter;
        $this->unitLabelFormatter             = $unitLabelFormatter;
        $this->unitValueFormatter             = $unitValueFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getCombinedPricesForProductsByPriceList(
        array $productRecords,
        CombinedPriceList $priceList,
        $currency
    ) {
        $productIds = array_map(
            function (ResultRecordInterface $record) {
                return $record->getValue('id');
            },
            $productRecords
        );

        // TODO: BB-14587 replace with price provider
        $combinedPrices = $this->combinedProductPriceRepository
            ->getPricesForProductsByPriceList($priceList, $productIds, $currency);

        // TODO: BB-14587 replace CombinedProductPrice with ProductPriceModel
        $resultProductPrices = [];
        usort($combinedPrices, function (CombinedProductPrice $a, CombinedProductPrice $b) {
            if ($a->getProductUnitCode() !==  $b->getProductUnitCode()) {
                return $a->getProductUnitCode() > $b->getProductUnitCode();
            }

            return  $a->getQuantity() > $b->getQuantity();
        });
        foreach ($combinedPrices as $price) {
            $index = sprintf('%s_%s', $price->getProductUnitCode(), $price->getQuantity());

            $productId = $price->getProduct()->getId();
            if (isset($resultProductPrices[$productId][$index])) {
                continue;
            }

            $resultProductPrices[$productId][$index] = $this->prepareResultPrice($price);
        }

        return $resultProductPrices;
    }

    /**
     * @param CombinedProductPrice $price
     * @return array
     */
    private function prepareResultPrice(CombinedProductPrice $price)
    {
        $priceValue      = $price->getPrice()->getValue();
        $unitCode        = $price->getUnit()->getCode();
        $quantity        = $price->getQuantity();
        $currencyIsoCode = $price->getPrice()->getCurrency();

        $resultPrices = [
            'price'              => $priceValue,
            'currency'           => $currencyIsoCode,
            'formatted_price'    => $this->numberFormatter->formatCurrency($priceValue, $currencyIsoCode),
            'unit'               => $unitCode,
            'formatted_unit'     => $this->unitLabelFormatter->format($unitCode),
            'quantity'           => $quantity,
            'quantity_with_unit' => $this->unitValueFormatter->formatCode($quantity, $unitCode)
        ];

        return $resultPrices;
    }
}
