<?php

namespace Oro\Bundle\PricingBundle\Datagrid\Provider;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides Product Price formatted variables (price, unit, quantity_with_unit) for views
 */
class ProductPriceProvider
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
     * @param ResultRecordInterface[] $productRecords
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param string|null $currency
     * @return array
     */
    public function getPricesForProductsByPriceList(
        array $productRecords,
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        string $currency
    ) {
        $products = $this->getProducts($productRecords);
        $prices = $this->getPrices($scopeCriteria, $products, $currency);

        $resultProductPrices = [];
        foreach ($prices as $productId => $productPrices) {
            /** @var ProductPriceInterface $price */
            foreach ($productPrices as $price) {
                $index = sprintf('%s_%s', $price->getUnit()->getCode(), $price->getQuantity());
                if (isset($resultProductPrices[$productId][$index])) {
                    continue;
                }

                $resultProductPrices[$productId][$index] = $this->priceFormatter->formatProductPrice($price);
            }
        }

        return $resultProductPrices;
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param Product[] $products
     * @param string $currency
     * @return array
     */
    protected function getPrices(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $products,
        string $currency
    ): array {
        $prices = $this->productPriceProvider
            ->getPricesByScopeCriteriaAndProducts($scopeCriteria, $products, [$currency]);

        foreach ($prices as &$productPrices) {
            usort($productPrices, static function (ProductPriceInterface $a, ProductPriceInterface $b) {
                if ($a->getUnit()->getCode() !== $b->getUnit()->getCode()) {
                    return $a->getUnit()->getCode() <=> $b->getUnit()->getCode();
                }

                return $a->getQuantity() <=> $b->getQuantity();
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
