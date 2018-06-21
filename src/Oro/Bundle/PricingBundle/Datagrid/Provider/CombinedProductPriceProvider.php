<?php

namespace Oro\Bundle\PricingBundle\Datagrid\Provider;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;

class CombinedProductPriceProvider
{
    /**
     * @var ProductPriceProviderInterface
     */
    private $productPriceProvider;

    /**
     * @var ProductPriceFormatter
     */
    private $priceFormatter;

    /**
     * @param ProductPriceProviderInterface $productPriceProvider
     * @param ProductPriceFormatter $priceFormatter
     */
    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceFormatter $priceFormatter
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceFormatter = $priceFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getCombinedPricesForProductsByPriceList(
        array $productRecords,
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        $currency
    ) {
        $productIds = $this->getProductIds($productRecords);
        $prices = $this->getPrices($scopeCriteria, $currency, $productIds);

        $resultProductPrices = [];
        foreach ($prices as $productId => $productPrices) {
            foreach ($productPrices as $price) {
                $index = sprintf('%s_%s', $price['unit'], $price['quantity']);
                if (isset($resultProductPrices[$productId][$index])) {
                    continue;
                }

                $resultProductPrices[$productId][$index] = $this->priceFormatter->formatProductPriceData($price);
            }
        }

        return $resultProductPrices;
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param string $currency
     * @param array $productIds
     * @return array
     */
    protected function getPrices(ProductPriceScopeCriteriaInterface $scopeCriteria, $currency, $productIds): array
    {
        $prices = $this->productPriceProvider
            ->getPricesByScopeCriteriaAndProductIds($scopeCriteria, $productIds, $currency);

        foreach ($prices as &$productPrices) {
            usort($productPrices, function (array $a, array $b) {
                if ($a['unit'] !== $b['unit']) {
                    return $a['unit'] > $b['unit'];
                }

                return $a['quantity'] > $b['quantity'];
            });
        }

        return $prices;
    }

    /**
     * @param array|ResultRecordInterface[] $productRecords
     * @return array
     */
    protected function getProductIds(array $productRecords): array
    {
        $productIds = array_map(
            function (ResultRecordInterface $record) {
                return $record->getValue('id');
            },
            $productRecords
        );

        return $productIds;
    }
}
