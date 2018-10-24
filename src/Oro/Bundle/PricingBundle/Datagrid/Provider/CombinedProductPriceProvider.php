<?php

namespace Oro\Bundle\PricingBundle\Datagrid\Provider;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Realization of CombinedProductPriceProviderInterface
 * Provides CombinedProductPrice formatted variables (price, unit, quantity_with_unit) for views
 */
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
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param ProductPriceProviderInterface $productPriceProvider
     * @param ProductPriceFormatter $priceFormatter
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceFormatter $priceFormatter,
        DoctrineHelper $doctrineHelper
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceFormatter = $priceFormatter;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getCombinedPricesForProductsByPriceList(
        array $productRecords,
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        $currency
    ) {
        $products = $this->getProducts($productRecords);
        $prices = $this->getPrices($scopeCriteria, $currency, $products);

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
     * @param array|Product[] $products
     * @return array
     */
    protected function getPrices(ProductPriceScopeCriteriaInterface $scopeCriteria, $currency, $products): array
    {
        $prices = $this->productPriceProvider
            ->getPricesByScopeCriteriaAndProducts($scopeCriteria, $products, $currency);

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
     * @return array|Product[]
     */
    protected function getProducts(array $productRecords): array
    {
        return array_map(
            function (ResultRecordInterface $record) {
                return $this->doctrineHelper->getEntityReference(Product::class, $record->getValue('id'));
            },
            $productRecords
        );
    }
}
