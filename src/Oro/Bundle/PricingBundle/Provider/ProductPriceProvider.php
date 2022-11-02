<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Read prices from storage and return requested prices to the client in expected format.
 */
class ProductPriceProvider implements ProductPriceProviderInterface
{
    use MemoryCacheProviderAwareTrait;

    /**
     * @var ProductPriceStorageInterface
     */
    protected $priceStorage;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    public function __construct(
        ProductPriceStorageInterface $priceStorage,
        UserCurrencyManager $currencyManager
    ) {
        $this->priceStorage = $priceStorage;
        $this->currencyManager = $currencyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria): array
    {
        return array_intersect(
            $this->currencyManager->getAvailableCurrencies(),
            $this->priceStorage->getSupportedCurrencies($scopeCriteria)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPricesByScopeCriteriaAndProducts(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $products,
        array $currencies,
        string $unitCode = null
    ): array {
        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);
        if (empty($currencies)) {
            // There is no sense to get prices because of no allowed currencies present.
            return [];
        }

        $productsIds = [];
        foreach ($products as $product) {
            $productId = is_a($product, Product::class) ? $product->getId() : (int) $product;
            $productsIds[$productId] = $productId;
        }

        $productUnitCodes = $unitCode ? [$unitCode] : null;
        $prices = $this->getPrices($scopeCriteria, $productsIds, $productUnitCodes, $currencies);

        $result = [];
        foreach ($prices as $price) {
            $result[$price->getProduct()->getId()][] = $price;
        }

        return $result;
    }

    /**
     * @param array $productPriceCriteria
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     *
     * @return Price[]
     */
    public function getMatchedPrices(
        array $productPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array {
        return $this->getActualMatchedPrices($productPriceCriteria, $scopeCriteria);
    }

    /**
     * @param array $productPriceCriteria
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     *
     * @return Price[]
     */
    protected function getActualMatchedPrices(
        array $productPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array {
        $productsIds = [];
        $productUnitCodes = [];
        $currencies = [];
        $result = [];

        /** @var ProductPriceCriteria $productPriceCriterion */
        foreach ($productPriceCriteria as $productPriceCriterion) {
            $productUnitCode = $productPriceCriterion->getProductUnit()->getCode();
            $currency = $productPriceCriterion->getCurrency();
            $productId = $productPriceCriterion->getProduct()->getId();

            $productsIds[$productId] = $productId;
            $productUnitCodes[$productUnitCode] = $productUnitCode;
            $currencies[$currency] = $currency;
        }

        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);
        $prices = $this->getPrices($scopeCriteria, $productsIds, $productUnitCodes, $currencies);

        $productPriceData = [];
        foreach ($prices as $priceData) {
            $key = $this->getKey(
                $priceData->getProduct(),
                $priceData->getUnit(),
                $priceData->getPrice()->getCurrency()
            );

            $productPriceData[$key][] = $priceData;
        }

        foreach ($productPriceCriteria as $productPriceCriterion) {
            $quantity = $productPriceCriterion->getQuantity();
            $currency = $productPriceCriterion->getCurrency();
            $key = $this->getKey(
                $productPriceCriterion->getProduct(),
                $productPriceCriterion->getProductUnit(),
                $currency
            );

            $price = $this->matchPriceByQuantity($productPriceData[$key] ?? [], $quantity);

            $identifier = $productPriceCriterion->getIdentifier();
            $result[$identifier] = $price !== null ? Price::create($price, $currency) : null;
        }

        return $result;
    }

    /**
     * @param ProductPriceInterface[] $prices
     */
    private function sortPrices(array &$prices)
    {
        usort($prices, static function (ProductPriceDTO $a, ProductPriceDTO $b) {
            $codeA = $a->getUnit()->getCode();
            $codeB = $b->getUnit()->getCode();
            if ($codeA === $codeB) {
                return $a->getQuantity() <=> $b->getQuantity();
            }

            return $codeA <=> $codeB;
        });
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $productsIds
     * @param array|null $productUnitCodes
     * @param array|null $currencies
     *
     * @return ProductPriceInterface[]
     */
    private function getPrices(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $productsIds,
        array $productUnitCodes = null,
        array $currencies = null
    ): array {
        if (!$currencies) {
            // There is no sense to get prices when no allowed currencies present.
            return [];
        }

        return (array) $this->getMemoryCacheProvider()->get(
            [
                'product_price_scope_criteria' => $scopeCriteria,
                $productsIds,
                $currencies,
                $productUnitCodes,
            ],
            function () use ($scopeCriteria, $productsIds, $productUnitCodes, $currencies) {
                $prices = $this->priceStorage->getPrices($scopeCriteria, $productsIds, $productUnitCodes, $currencies);
                $this->sortPrices($prices);

                return $prices;
            }
        );
    }

    private function getKey(Product $product, MeasureUnitInterface $unit, string $currency): string
    {
        return sprintf('%s|%s|%s', $product->getId(), $unit->getCode(), $currency);
    }

    /**
     * @param array|ProductPriceInterface[] $pricesData
     * @param float $expectedQuantity
     * @return float|null
     */
    protected function matchPriceByQuantity(array $pricesData, $expectedQuantity): ?float
    {
        $price = null;
        foreach ($pricesData as $priceData) {
            $quantity = $priceData->getQuantity();

            if ($expectedQuantity >= $quantity) {
                $price = $priceData->getPrice()->getValue();
            }

            if ($expectedQuantity <= $quantity) {
                // Matching price has been already found, break from loop.
                break;
            }
        }

        return $price;
    }

    /**
     * Restrict currencies list to getSupportedCurrencies
     */
    protected function getAllowedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria, array $currencies): array
    {
        if (empty($currencies)) {
            return $currencies;
        }

        $currencies = array_intersect($currencies, $this->getSupportedCurrencies($scopeCriteria));
        return $currencies;
    }
}
