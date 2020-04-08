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

    /**
     * @param ProductPriceStorageInterface $priceStorage
     * @param UserCurrencyManager $currencyManager
     */
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
        return $this->getMemoryCacheProvider()->get(
            ['product_price_scope_criteria' => $scopeCriteria],
            function () use ($scopeCriteria) {
                return array_intersect(
                    $this->currencyManager->getAvailableCurrencies(),
                    $this->priceStorage->getSupportedCurrencies($scopeCriteria)
                );
            }
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
    ):array {
        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);
        if (empty($currencies)) {
            // There is no sense to get prices because of no allowed currencies present.
            return [];
        }

        $productsIds = [];
        foreach ($products as $product) {
            $productId = $product->getId();
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
     * @return array
     */
    public function getMatchedPrices(
        array $productPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array {
        return $this->getMemoryCacheProvider()->get(
            [
                'product_price_criteria' => array_values($productPriceCriteria),
                'product_price_scope_criteria' => $scopeCriteria,
            ],
            function () use ($productPriceCriteria, $scopeCriteria) {
                return $this->getActualMatchedPrices($productPriceCriteria, $scopeCriteria);
            }
        );
    }

    /**
     * @param array $productPriceCriteria
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     *
     * @return array
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
            $currency = $productPriceCriterion->getCurrency();
            $key = $this->getKey(
                $productPriceCriterion->getProduct(),
                $productPriceCriterion->getProductUnit(),
                $currency
            );
            $quantity = $productPriceCriterion->getQuantity();
            $price = $this->matchPriceByQuantity($productPriceData[$key] ?? [], $quantity);
            if ($price !== null) {
                $result[$productPriceCriterion->getIdentifier()] = Price::create($price, $currency);
            } else {
                $result[$productPriceCriterion->getIdentifier()] = null;
            }
        }

        return $result;
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $productsIds
     * @param array|null $productUnitCodes
     * @param array|null $currencies
     *
     * @return array
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

        $allPrices = null;
        if ($productUnitCodes) {
            /** @var ProductPriceDTO[]|null $allPrices */
            $allPrices = $this->getMemoryCacheProvider()->get(
                [
                    'product_price_scope_criteria' => $scopeCriteria,
                    $productsIds,
                    $currencies,
                    null,
                ]
            );
        }

        return (array) $this->getMemoryCacheProvider()->get(
            [
                'product_price_scope_criteria' => $scopeCriteria,
                $productsIds,
                $currencies,
                $productUnitCodes,
            ],
            function () use ($allPrices, $scopeCriteria, $productsIds, $productUnitCodes, $currencies) {
                if (!$allPrices) {
                    return $this->priceStorage->getPrices($scopeCriteria, $productsIds, $productUnitCodes, $currencies);
                }

                if ($productUnitCodes) {
                    // Fetch prices from the previously fetched $allPrices collection.
                    $prices = [];
                    foreach ($allPrices as $price) {
                        if (\in_array($price->getUnit()->getCode(), $productUnitCodes, false)) {
                            $prices[] = $price;
                        }
                    }

                    return $prices;
                }

                return $allPrices;
            }
        );
    }

    /**
     * @param Product $product
     * @param MeasureUnitInterface $unit
     * @param string $currency
     *
     * @return string
     */
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
        }

        return $price;
    }

    /**
     * Restrict currencies list to getSupportedCurrencies
     *
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $currencies
     * @return array
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
